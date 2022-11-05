<?php

namespace App\VideoBasedMarketing\Account\Api\Extension\V1\Controller;

use App\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Api\Extension\V1\Service\SessionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class SessionController
    extends AbstractController
{
    #[Route(
        path        : '%app.routing.route_prefix.api%/extension/v1/account/session-info',
        name        : 'videobasedmarketing.account.api.extension.v1.session_info',
        methods     : [Request::METHOD_GET, Request::METHOD_POST]
    )]
    public function getSessionInfoAction(
        SessionService $sessionService
    ): Response
    {
        return new JsonResponse(
            [
                'settings' =>
                    $sessionService->getSessionInfoArray($this->getUser())
            ]
        );
    }
}
