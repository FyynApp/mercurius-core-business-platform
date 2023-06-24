<?php

namespace App\VideoBasedMarketing\Recordings\Api\VideoUpload\V1\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoDomainService;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use TusPhp\Events\UploadComplete;
use TusPhp\Tus\Server;


class TusController
    extends AbstractController
{
    #[Route(
        path: '%app.routing.route_prefix.api%/recordings/video-upload/v1/tus/',
        name: 'videobasedmarketing.recordings.api.video_upload.v1.tus_patch',
        methods: [
            Request::METHOD_POST,
            Request::METHOD_PATCH,
            Request::METHOD_HEAD
        ]
    )]
    #[Route(
        path: '%app.routing.route_prefix.api%/recordings/video-upload/v1/tus/{token?}',
        name: 'videobasedmarketing.recordings.api.video_upload.v1.tus',
        requirements: ['token' => '.+'],
        methods: [
            Request::METHOD_POST,
            Request::METHOD_PATCH,
            Request::METHOD_HEAD
        ]
    )]
    public function videoUploadTusAction(
        ?string                         $token,
        Server                          $server,
        RecordingsInfrastructureService $recordingsInfrastructureService,
        CapabilitiesService             $capabilitiesService
    ): Response
    {
        /** @var null|User $user */
        $user = $this->getUser();

        if (is_null($user)) {
            throw new AccessDeniedHttpException('No user.');
        }

        $server->setApiPath('/api/recordings/video-upload/v1/tus');
        $server->getCache()->setPrefix($user->getId());
        $server->setMaxUploadSize(
            $capabilitiesService->getMaxVideoUploadFilesizeInBytes($user)
        );

        $recordingsInfrastructureService->prepareVideoUpload($user, $server);

        $server->event()->addListener(
            UploadComplete::NAME,
            function (UploadComplete $event)
                use ($recordingsInfrastructureService, $user, $token)
            {
                $fileMeta = $event->getFile()->details();

                $currentVideoFolderId = null;
                if (array_key_exists('metadata', $fileMeta)) {
                    if (array_key_exists('currentVideoFolderId', $fileMeta['metadata'])) {
                        $currentVideoFolderId = $fileMeta['metadata']['currentVideoFolderId'];
                    }
                }

                $recordingsInfrastructureService
                    ->handleCompletedVideoUpload(
                        $user,
                        $token,
                        $event,
                        $currentVideoFolderId
                    );
            }
        );

        return $server->serve();
    }
}
