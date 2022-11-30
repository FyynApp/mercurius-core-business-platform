<?php

namespace App\VideoBasedMarketing\Account\Api\Extension\V1\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\Shared\Infrastructure\Enum\DateTimeFormat;
use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Api\Extension\V1\Service\SessionService;
use App\VideoBasedMarketing\Account\Infrastructure\Enum\RequestParameter;
use App\VideoBasedMarketing\Account\Infrastructure\Security\RequestParametersBasedUserAuthenticator;
use App\VideoBasedMarketing\Account\Infrastructure\Service\AccountAssetsService;
use App\VideoBasedMarketing\Account\Infrastructure\Service\RequestParametersBasedUserAuthService;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class SessionController
    extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route(
        path        : '%app.routing.route_prefix.api%/extension/v1/account/session-info',
        name        : 'videobasedmarketing.account.api.extension.v1.session_info',
        methods     : [Request::METHOD_GET]
    )]
    public function getSessionInfoAction(
        SessionService                        $sessionService,
        AccountAssetsService                  $accountAssetsService,
        RequestParametersBasedUserAuthService $requestParametersBasedUserAuthService
    ): Response
    {
        $sessionInfo = $sessionService->getSessionInfo($this->getUser());

        if (   is_null($this->getUser())
            && !$sessionInfo->getUser()->isRegistered()
        ) {
            return $requestParametersBasedUserAuthService
                ->createRedirectResponse(
                    $sessionInfo->getUser(),
                    'videobasedmarketing.account.api.extension.v1.session_info'
                );
        }

        return new JsonResponse(
            [
                'settings' =>
                    [
                        'serviceAvailable' => true,

                        'userCanStoreVideoOnServer' => true,

                        'userIsLoggedIn' => true,

                        'userIsRegistered' => $sessionInfo->getUser()->isRegistered(),

                        'userName' => $sessionInfo->getUser()->getUserIdentifier(),

                        'userFirstName' => $sessionInfo->getUser()->getFirstName(),

                        'userLastName' => $sessionInfo->getUser()->getLastName(),

                        'userImage' => $accountAssetsService
                            ->getUrlForUserProfilePhoto($sessionInfo->getUser()),

                        'membershipPlan' => $sessionInfo
                            ->getMembershipPlan()
                            ->getName()
                            ->value
                    ]
            ]
        );
    }
}
