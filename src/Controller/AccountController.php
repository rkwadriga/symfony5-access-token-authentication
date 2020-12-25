<?php declare(strict_types=1);

namespace App\Controller;

use App\Form\UserForm;
use App\Security\AccessTokenAuthenticator;
use App\Security\AccessTokenUserProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Exception\HttpException;

class AccountController extends AbstractController
{
    private AccessTokenAuthenticator $authenticator;
    private AccessTokenUserProvider $userProvider;

    public function __construct(AccessTokenAuthenticator $authenticator, AccessTokenUserProvider $userProvider)
    {
        $this->authenticator = $authenticator;
        $this->userProvider = $userProvider;
    }

    /**
     * @param UserForm $form
     * @param Request $request
     * @return JsonResponse
     *
     * @Route("account", name="app_account_create", methods={"PUT"})
     */
    public function create(UserForm $form, Request $request): JsonResponse
    {
        try {
            $user = $form->create($request);
            return $this->json($user->getToken()->getCredentials());
        } catch (\Exception $e) {
            throw new HttpException($e);
        }
    }
}