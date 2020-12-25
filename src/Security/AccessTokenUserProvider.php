<?php declare(strict_types=1);

namespace App\Security;

use App\Entity\Token;
use App\Entity\User;
use App\Exception\AuthException;
use App\Helpers\AccessTokenHelper;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AccessTokenUserProvider implements UserProviderInterface
{
    private ManagerRegistry $doctrine;
    private UserPasswordEncoderInterface $encoder;
    private ContainerInterface $container;

    public function __construct(ManagerRegistry $registry, UserPasswordEncoderInterface $encoder, ContainerInterface $container)
    {
        $this->doctrine = $registry;
        $this->encoder = $encoder;
        $this->container = $container;
    }

    /**
     * Create a new user token
     *
     * @param Request $request
     * @return Token
     * @throws AuthException
     */
    public function loginUser(Request $request): TokenInterface
    {
        // 1. Check is request has required params
        $username = $request->get('username');
        $password = $request->get('password');
        if (empty($username) || empty($password)) {
            throw new AuthException('Params "username" and "password" area required!', Response::HTTP_BAD_REQUEST);
        }
        // 2. Try to find user by username and if it`s found check the password
        /** @var User $user */
        $user = $this->loadUserByUsername($username);
        if ($user === null || $this->encoder->isPasswordValid($user, $password) !== true) {
            throw new AuthException('Username or password incorrect', Response::HTTP_BAD_REQUEST);
        }
        // 3. Create and return user access token
        $token = $this->createAccessToken($user);
        $user->addToken($token)->setToken($token);

        $manager = $this->doctrine->getManager();
        try {
            $manager->persist($token);
            $manager->persist($user);
            $manager->flush();
        } catch (\Exception $e) {
            throw new AuthException(sprintf('Can`t login user: %s', $e->getMessage()), $e->getCode(), $e);
        }

        return $token;
    }

    /**
     * Update user token
     *
     * @param User $user
     * @param Request $request
     * @return Token
     * @throws AuthException
     */
    public function refreshUserToken(User $user, Request $request): TokenInterface
    {
        if (($refreshToken = $request->get('refresh_token')) === null) {
            throw new AuthException('Refresh token is missed', Response::HTTP_BAD_REQUEST);
        }
        // Check refresh token
        if ($user->getToken()->getRefreshToken() !== base64_decode($refreshToken)) {
            throw new AuthException('Invalid refresh token', Response::HTTP_FORBIDDEN);
        }
        // Check is refresh token is not expired
        if (AccessTokenHelper::isRefreshTokenExpired($user->getToken()->getUpdatedAt())) {
            throw new AuthException('Refresh token is expired', Response::HTTP_FORBIDDEN);
        }
        // Update token and return the user
        return $this->updateAccessToken($user);
    }

    public function logoutUser(User $user): void
    {
        $token = $user->getToken();
        /**
         * Remove user's token
         */
        $user->removeToken($token);

        $manager = $this->doctrine->getManager();
        try {
            $manager->persist($user);
            $manager->remove($token);

            $manager->flush();
        } catch (\Exception $e) {
            throw new AuthenticationException(sprintf('Can`t logout user: %s', $e->getMessage()), $e->getCode(), $e);
        }
    }

    /**
     * @param string $username
     * @return User|null
     */
    public function loadUserByUsername(string $username): ?UserInterface
    {
        /** @var UserRepository $repository */
        $repository = $this->doctrine->getRepository(User::class);
        return $repository->findOneBy(['email' => $username]);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        $user->eraseCredentials();
        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return true;
    }

    /**
     * Create an access token object with successfully authenticated user credentials
     * This method should run only if user is authenticated correctly!
     *
     * @param UserInterface $user
     * @return Token
     */
    public function createAccessToken(UserInterface $user): TokenInterface
    {
        $token = new Token();
        return $token->setAccessToken(AccessTokenHelper::generateAccessToken($user))
            ->setRefreshToken(AccessTokenHelper::generateAccessToken($user))
            ->setCreatedAt(new \DateTime())
            ->setExpiredAt(AccessTokenHelper::getAccessTokenExpiredAt())
            ->setUser($user)
        ;
    }


    /**
     * Update an access token object with successfully authenticated user credentials
     *
     * @param User $user
     * @return Token
     * @throws \App\Exception\AuthException
     */
    private function updateAccessToken(User $user): TokenInterface
    {
        $token = $user->getToken()->setAccessToken(AccessTokenHelper::generateAccessToken($user))
            ->setRefreshToken(AccessTokenHelper::generateAccessToken($user))
            ->setExpiredAt(AccessTokenHelper::getAccessTokenExpiredAt())
        ;
        $manager = $this->doctrine->getManager();
        try {
            $manager->persist($token);
            $manager->flush();
        } catch (\Exception $e) {
            throw new AuthenticationException(sprintf('Can`t refresh token: %s', $e->getMessage()), $e->getCode(), $e);
        }
        return $token;
    }
}