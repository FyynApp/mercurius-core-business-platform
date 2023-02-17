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
        name        : 'videobasedmarketing.account.presentation.sign_in',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET, Request::METHOD_POST]
    )]
    public function signInFormAction(
        AuthenticationUtils   $authenticationUtils,
        ThirdPartyAuthService $thirdPartyAuthService,
        Request               $request
    ): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if (   !is_null($error)
            && $thirdPartyAuthService->userMustBeRedirectedToThirdPartyAuthLinkedinEndpoint($lastUsername)
        ) {
            return $this->redirectToRoute('videobasedmarketing.account.infrastructure.thirdpartyauth.linkedin.start');
        }

        if (trim($lastUsername) === '') {
            $lastUsername = (string)$request->get('username');
        }

        return $this->render(
            '@videobasedmarketing.account/sign_in/form.html.twig',
            [
                'username' => $lastUsername,
                'error' => $error,
            ]
        );
    }


    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/account/sign-in/forgot-password',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/benutzerkonto/einloggen/password-vergessen',
        ],
        name        : 'videobasedmarketing.account.presentation.forgot_password_form',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function forgotPasswordFormAction(): Response
    {
        return $this->render(
            '@videobasedmarketing.account/sign_in/forgot_password_form.html.twig'
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/account/sign-in/forgot-password/handle',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/benutzerkonto/einloggen/password-vergessen/verarbeiten',
        ],
        name        : 'videobasedmarketing.account.presentation.forgot_password_handle_form',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function forgotPasswordHandleFormAction(
        Request $request
    ): Response
    {
        return $this->redirectToRoute(
            'videobasedmarketing.account.presentation.forgot_password_thanks',
            ['email' => $request->get('email')],
            Response::HTTP_SEE_OTHER
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/account/sign-in/forgot-password/thanks',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/benutzerkonto/einloggen/password-vergessen/danke',
        ],
        name        : 'videobasedmarketing.account.presentation.forgot_password_thanks',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function forgotPasswordThanksAction(
        Request $request
    ): Response
    {
        return $this->render(
            '@videobasedmarketing.account/sign_in/forgot_password_thanks.html.twig',
            ['email' => $request->get('email')]
        );
    }
}
