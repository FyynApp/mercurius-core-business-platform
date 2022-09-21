<?php

namespace App\Controller\Feature\Account;

use App\Controller\AbstractController;
use App\Entity\Feature\Account\User;
use App\Service\Feature\Account\AccountService;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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


    /**
     * @throws Exception
     */
    public function createUnregisteredUserAction(): Response
    {
        $user = $this->getUser();

        if (!is_null($user)) {
            throw new BadRequestHttpException("User {$user->getUserIdentifier()} is already logged in.");
        }

        // @TODO: Own symfony role for unregistered!
        // https://symfony.com/doc/current/security/custom_authenticator.html

        $user = new User();
        $user->setEmail('unregistered_user_' . password_hash('fh45897z784787h!8997/%drh==iuh' . random_int(PHP_INT_MIN,  PHP_INT_MAX) . random_int(PHP_INT_MIN,  PHP_INT_MAX), PASSWORD_DEFAULT) . '@unregistered.fyyn.io');

        throw new NotFoundHttpException();
    }
}
