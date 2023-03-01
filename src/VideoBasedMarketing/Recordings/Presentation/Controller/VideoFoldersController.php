<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Enum\VotingAttribute;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoFolder;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoFolderDomainService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        $parentVideoFolderId = $request->get('parentVideoFolderId');

        $parentVideoFolder = null;

        if (!is_null($parentVideoFolderId)) {
            $r = $this->verifyAndGetUserAndEntity(
                VideoFolder::class,
                $parentVideoFolderId,
                VotingAttribute::Use
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
            (string)$request->get('name'),
            $parentVideoFolder
        );

        if (is_null($videoFolder)) {
            throw $this->createAccessDeniedException('Could not create video folder.');
        }

        return $this
            ->redirectToRoute(
                'videobasedmarketing.recordings.presentation.videos.overview',
                ['videoFolderId' => $parentVideoFolderId]
            );
    }
}
