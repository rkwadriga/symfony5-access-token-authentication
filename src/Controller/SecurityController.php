<?php declare(strict_types=1);

namespace App\Controller;

use App\Exception\HttpException;
use App\Security\AccessTokenUserProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    private AccessTokenUserProvider $userProvider;

    public function __construct(AccessTokenUserProvider $userProvider)
    {
        $this->userProvider = $userProvider;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @Route("/token", name="app_security_createtoken", methods={"PUT"})
     */
    public function createToken(Request $request): JsonResponse
    {
        try {
            return $this->json($this->userProvider->loginUser($request)->getCredentials());
        } catch (\Exception $e) {
            throw new HttpException($e);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @Route("/token", name="app_security_updatetoken", methods={"POST"})
     */
    public function updateToken(Request $request): JsonResponse
    {
        try {
            return $this->json($this->userProvider->refreshUserToken($this->getUser(), $request)->getCredentials());
        } catch (\Exception $e) {
            throw new HttpException($e);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @Route("/token", name="app_security_deletetoken", methods={"DELETE"})
     */
    public function deleteToken(): JsonResponse
    {
        try {
            $this->userProvider->logoutUser($this->getUser());
            return $this->json('OK');
        } catch (\Exception $e) {
            throw new HttpException($e);
        }
    }
}