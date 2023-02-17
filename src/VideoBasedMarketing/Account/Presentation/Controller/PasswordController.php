<?php

namespace App\VideoBasedMarketing\Account\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Infrastructure\Service\ThirdPartyAuthService;
use App\VideoBasedMarketing\Account\Presentation\Service\AccountPresentationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;


class PasswordController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/account/change-password',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/benutzerkonto/passwort-ändern',
        ],
        name        : 'videobasedmarketing.account.presentation.password.change',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function forgotPasswordResetAction(
        Request $request
    ): Response
    {
        return $this->render(
            'videobasedmarketing.account/password/change.html.twig'
        );
    }
}
