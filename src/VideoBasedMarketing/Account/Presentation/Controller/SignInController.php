<?php

namespace App\VideoBasedMarketing\Account\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Infrastructure\Service\ThirdPartyAuthService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;


class SignInController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/account/sign-in',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/benutzerkonto/einloggen',
        ],
        name        : 'videobasedmarketing.account.sign_in',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET, Request::METHOD_POST]
    )]
    public function indexAction(
        AuthenticationUtils   $authenticationUtils,
        ThirdPartyAuthService $thirdPartyAuthService
    ): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if (   !is_null($error)
            && $thirdPartyAuthService->userMustBeRedirectedToThirdPartyAuthLinkedinEndpoint($lastUsername)
        ) {
            return $this->redirectToRoute('videobasedmarketing.account.thirdpartyauth.linkedin.start');
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
