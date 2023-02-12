<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Service\AccountDomainService;
use App\VideoBasedMarketing\Account\Infrastructure\Service\RequestParametersBasedUserAuthService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class VideoUploadController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/recordings/upload-video',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/aufnahmen/video-hochladen',
        ],
        name        : 'videobasedmarketing.recordings.presentation.upload_video',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function uploadVideoAction(
        AccountDomainService                  $accountDomainService,
        RequestParametersBasedUserAuthService $requestParametersBasedUserAuthService,
    ): Response
    {
        /** @var null|User $user */
        $user = $this->getUser();

        if (is_null($user)) {
            $user = $accountDomainService->createUnregisteredUser();
            return $requestParametersBasedUserAuthService->createRedirectResponse(
                $user,
                'videobasedmarketing.recordings.presentation.upload_video'
            );
        }

        return $this->render(
            '@videobasedmarketing.recordings/upload_video.html.twig'
        );
    }
}
