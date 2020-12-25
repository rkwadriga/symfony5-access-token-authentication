<?php declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use App\Security\AccessTokenUserProvider;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
use App\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Faker\Factory;

class UserForm extends AbstractForm
{
    private UserPasswordEncoderInterface $passwordEncoder;
    private AccessTokenUserProvider $userProvider;

    public function __construct(ValidatorInterface $validator, ManagerRegistry $registry, UserPasswordEncoderInterface $passwordEncoder, AccessTokenUserProvider $userProvider)
    {
        parent::__construct($validator, $registry);
        $this->passwordEncoder = $passwordEncoder;
        $this->userProvider = $userProvider;
    }

    public function create(Request $request): User
    {
        $entityManager = $this->doctrine->getManager();

        $user = new User();
        $faker = Factory::create();
        $user->setSalt(hash('sha256', $faker->uuid));
        $this->setAttributes($user, $request);

        $token = $this->userProvider->createAccessToken($user);
        $user->setToken($token)->addToken($token);

        $entityManager->persist($token);
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    public function update(User $user, Request $request): User
    {
        $entityManager = $this->doctrine->getManager();

        $user = new User();
        $this->setAttributes($user, $request);

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    private function setAttributes(User $user, Request $request)
    {
        if ($request->get('username') !== null) {
            $user->setEmail($request->get('username'));
        }
        if ($request->get('name') !== null) {
            $user->setName($request->get('name'));
        }
        if ($request->get('password') !== null) {
            $user->setPassword($this->passwordEncoder->encodePassword($user, $request->get('password')));
        }
        $errors = $this->validator->validate($user);
        if ($errors->count() > 0) {
            throw new ValidationFailedException((string)$errors, $errors);
        }
    }
}