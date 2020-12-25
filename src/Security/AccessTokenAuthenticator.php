<?php

namespace App\Security;

use App\Entity\Token;
use App\Entity\User;
use App\Exception\AuthException;
use App\Repository\TokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class AccessTokenAuthenticator extends AbstractGuardAuthenticator
{
    const AUTH_TOKEN_PARAM_NAME = 'X-AUTH-TOKEN';

    private EntityManagerInterface $doctrine;
    private bool $isUpdating = false;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->doctrine = $manager;
    }

    public function supports(Request $request): bool
    {
        //return false;
        $this->isUpdating = $request->get('_route') == 'app_security_updatetoken';
        return !in_array($request->get('_route'), $this->getUnsopportedUrls());
    }

    public function getCredentials(Request $request): array
    {
        if (!$request->headers->has(self::AUTH_TOKEN_PARAM_NAME)) {
            return [];
        }
        return [
            'access_token' => base64_decode($request->headers->get(self::AUTH_TOKEN_PARAM_NAME)),
        ];
    }

    /**
     * @param array $credentials
     * @param AccessTokenUserProvider $userProvider
     * @return User|null
     * @throws AuthException
     */
    public function getUser($credentials, UserProviderInterface $userProvider): ?UserInterface
    {
        if (!isset($credentials['access_token'])) {
            throw new AuthException('Access token missed', Response::HTTP_FORBIDDEN);
        }

        /** @var TokenRepository $repository */
        $repository = $this->doctrine->getRepository(Token::class);
        $token = $repository->findOneBy(['access_token' => (string)$credentials['access_token']]);

        if ($token === null) {
            throw new AuthException('Invalid access token', Response::HTTP_FORBIDDEN);
        }
        // Check is access token not expired
        if (!$this->isUpdating && $token->getExpiredAt() < (new \DateTime())) {
            throw new AuthException('Access token expired', Response::HTTP_UNAUTHORIZED);
        }

        return $token->getUser()->setToken($token);
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey): ?Response
    {
        return null;
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }

    /**
     * Called when authentication is needed, but it's not sent
     *
     * @param Request $request
     * @param AuthenticationException|null $authException
     * @throws AuthException
     */
    public function start(Request $request, AuthenticationException $authException = null): void
    {
        throw new AuthException('Authentication Required', Response::HTTP_UNAUTHORIZED, $authException);
    }

    private function getUnsopportedUrls(): array
    {
        return [
            'app_security_createtoken',
            'app_account_create',
            '_preview_error',
            '_wdt',
            '_profiler_home',
            '_profiler_search',
            '_profiler_search_bar',
            '_profiler_phpinfo',
            '_profiler_search_results',
            '_profiler_open_file',
            '_profiler',
            '_profiler_router',
            '_profiler_exception',
            '_profiler_exception_css',
        ];
    }
}
