<?php

namespace App\VideoBasedMarketing\Account\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Infrastructure\Enum\RequestParameter;
use App\VideoBasedMarketing\Account\Infrastructure\Service\ThirdPartyAuthService;
use App\VideoBasedMarketing\Account\Presentation\Service\AccountPresentationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;


class AuthForAppController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/account/auth-for-app/success',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/benutzerkonto/auth-für-app/erfolg',
        ],
        name        : 'videobasedmarketing.account.presentation.auth_for_app.success',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function successAction(): Response
    {
        return $this->render(
            '@videobasedmarketing.account/auth_for_app/success.html.twig'
        );
    }
}
