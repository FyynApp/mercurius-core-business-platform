<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Enum\AccessAttribute;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoFolder;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoFolderDomainService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;


class VideoFoldersController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/recordings/video-folders/',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/aufnahmen/video-ordner/',
        ],
        name        : 'videobasedmarketing.recordings.presentation.video_folders.create',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function createVideoFolderAction(
        Request                  $request,
        VideoFolderDomainService $videoFolderDomainService
    ): Response
    {
        if (!$this->isCsrfTokenValid('create-video-folder', $request->get('_csrf_token'))) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        $parentVideoFolderId = $request->get('parentVideoFolderId');

        $name = trim((string)$request->get('name'));

        if ($name === '') {
            return $this
                ->redirectToRoute(
                    'videobasedmarketing.recordings.presentation.videos.overview',
                    ['videoFolderId' => $parentVideoFolderId]
                );
        }

        $parentVideoFolder = null;

        if (!is_null($parentVideoFolderId)) {
            $r = $this->verifyAndGetUserAndEntity(
                VideoFolder::class,
                $parentVideoFolderId,
                AccessAttribute::Use
            );

            /** @var VideoFolder $parentVideoFolder */
            $parentVideoFolder = $r->getEntity();

            $user = $r->getUser();
        } else {
            /** @var User $user */
            $user = $this->getUser();
        }

        $videoFolder = $videoFolderDomainService->createVideoFolder(
            $user,
            $name,
            $parentVideoFolder
        );

        if (is_null($videoFolder)) {
            throw new BadRequestHttpException('Could not create video folder.');
        }

        return $this
            ->redirectToRoute(
                'videobasedmarketing.recordings.presentation.videos.overview',
                ['videoFolderId' => $parentVideoFolderId]
            );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/recordings/video-folders/move-video',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/aufnahmen/video-ordner/video-verschieben',
        ],
        name        : 'videobasedmarketing.recordings.presentation.video_folders.move_video',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function moveVideoIntoFolderAction(
        Request                  $request,
        VideoFolderDomainService $videoFolderDomainService
    ): Response
    {
        $r = $this->verifyAndGetUserAndEntity(
            Video::class,
            $request->get('videoId'),
            AccessAttribute::Edit
        );

        /** @var Video $video */
        $video = $r->getEntity();

        if (!$this->isCsrfTokenValid("move-video-into-folder-{$video->getId()}", $request->get('_csrf_token'))) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        $videoFolderId = $request->get('videoFolderId');
        if (trim($videoFolderId) === '') {
            $videoFolderId = null;
        }

        $videoFolder = null;

        if (!is_null($videoFolderId)) {
            $r = $this->verifyAndGetUserAndEntity(
                VideoFolder::class,
                $videoFolderId,
                AccessAttribute::Use
            );

            /** @var VideoFolder $videoFolder */
            $videoFolder = $r->getEntity();
        }

        $videoFolderDomainService->moveVideoIntoFolder(
            $video,
            $videoFolder
        );

        return new JsonResponse();
    }
}
