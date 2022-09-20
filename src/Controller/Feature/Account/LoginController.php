<?php

namespace App\Controller\Feature\Account;

use App\Service\Feature\Account\AccountService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    public function indexAction(
        AuthenticationUtils $authenticationUtils,
        AccountService $accountService
    ): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if (   !is_null($error)
            && $accountService->userMustBeRedirectedToThirdPartyAuthLinkedinEndpoint($lastUsername)
        ) {
            return $this->redirectToRoute('feature.account.3rdpartyauth.linkedin.start');
        }

        return $this->render('feature/account/login/index.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }


    public function createUnregisteredUserAction(): Response
    {
        // @TODO: Own symfony role for unregistered!
        // https://symfony.com/doc/current/security/custom_authenticator.html

        throw new NotFoundHttpException();
    }
}
