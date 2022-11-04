<?php

namespace App\VideoBasedMarketing\Account\Presentation\Controller;

use App\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Service\AccountService;
use App\VideoBasedMarketing\Account\Infrastructure\Service\ThirdPartyAuthService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;


class SignInController
    extends AbstractController
{
    #[Route(
        path        : '{_locale}/account/sign-in',
        name        : '@videobasedmarketing.account.sign_in',
        requirements: ['_locale' => '%app.route_locale_requirement%'],
        methods     : [Request::METHOD_GET, Request::METHOD_POST]
    )]
    public function indexAction(
        AuthenticationUtils $authenticationUtils,
        AccountService      $accountService
    ): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if (!is_null($error)
            && ThirdPartyAuthService::userMustBeRedirectedToThirdPartyAuthLinkedinEndpoint($lastUsername, $accountService)
        ) {
            return $this->redirectToRoute('feature.account.3rdpartyauth.linkedin.start');
        }

        return $this->render(
            '@videobasedmarketing.account/sign_in/form.html.twig',
            [
                'last_username' => $lastUsername,
                'error' => $error,
            ]
        );
    }
}
