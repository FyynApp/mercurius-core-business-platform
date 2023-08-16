<?php

namespace App\VideoBasedMarketing\Account\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Infrastructure\Enum\RequestParameter;
use App\VideoBasedMarketing\Account\Infrastructure\Service\ThirdPartyAuthService;
use App\VideoBasedMarketing\Account\Presentation\Service\AccountPresentationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
        $isAuthForApp = $this->valueifyBoolParameter(RequestParameter::IsAuthForApp, $request);

        $request->getSession()->set(
            RequestParameter::IsAuthForApp->value,
            $isAuthForApp
        );

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if (   !is_null($error)
            && $thirdPartyAuthService->userMustBeRedirectedToThirdPartyAuthLinkedinEndpoint($lastUsername)
        ) {
            return $this->redirectToRoute(
                'videobasedmarketing.account.infrastructure.thirdpartyauth.linkedin.start',
                [RequestParameter::IsAuthForApp->value => $this->urlifyBoolValue($isAuthForApp)]
            );
        }

        if (trim($lastUsername) === '') {
            $lastUsername = (string)$request->get('username');
        }

        return $this->render(
            '@videobasedmarketing.account/sign_in/form.html.twig',
            [
                'username' => $lastUsername,
                'error' => $error,
                'isAuthForApp' => $isAuthForApp
            ]
        );
    }


    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/account/sign-in/forgot-password/request-reset',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/benutzerkonto/einloggen/password-vergessen/zurücksetzen-anfordern',
        ],
        name        : 'videobasedmarketing.account.presentation.sign_in.forgot_password.request_reset',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function forgotPasswordRequestResetAction(): Response
    {
        return $this->render(
            '@videobasedmarketing.account/sign_in/forgot_password/request_reset.html.twig'
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/account/sign-in/forgot-password/handle-request-reset',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/benutzerkonto/einloggen/password-vergessen/zurücksetzen-anfordern-verarbeiten',
        ],
        name        : 'videobasedmarketing.account.presentation.sign_in.forgot_password.handle_request_reset',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function forgotPasswordHandleRequestResetAction(
        Request                    $request,
        AccountPresentationService $accountPresentationService
    ): Response
    {
        if (!$this->isCsrfTokenValid('request-reset', $request->get('_csrf_token'))) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        /** @var null|string $email */
        $email = $request->get('email');

        if (is_null($email)) {
            throw new BadRequestHttpException('No email given.');
        }

        $accountPresentationService->sendPasswordResetEmail($request->get('email'));

        return $this->redirectToRoute(
            'videobasedmarketing.account.presentation.sign_in.forgot_password.request_reset.thanks',
            ['email' => $request->get('email')],
            Response::HTTP_SEE_OTHER
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/account/sign-in/forgot-password/request-reset/thanks',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/benutzerkonto/einloggen/password-vergessen/zurücksetzen-anfordern/danke',
        ],
        name        : 'videobasedmarketing.account.presentation.sign_in.forgot_password.request_reset.thanks',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function forgotPasswordRequestResetThanksAction(
        Request $request
    ): Response
    {
        return $this->render(
            '@videobasedmarketing.account/sign_in/forgot_password/request_reset_thanks.html.twig',
            ['email' => $request->get('email')]
        );
    }
}
