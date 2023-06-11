<?php

namespace App\VideoBasedMarketing\LingoSync\Presentation\Controller;

use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\Shared\Infrastructure\Controller\AbstractController;
use App\Shared\Presentation\Enum\FlashMessageLabel;
use App\VideoBasedMarketing\Account\Domain\Enum\AccessAttribute;
use App\VideoBasedMarketing\LingoSync\Domain\Service\LingoSyncDomainService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Presentation\Controller\VideoFoldersController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;


class LingoSyncProcessingController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/lingo-sync-processings/',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/lingo-sync-verarbeitungen/',
        ],
        name        : 'videobasedmarketing.lingo_sync.presentation.processing.start',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function startProcessingAction(
        Request                         $request,
        LingoSyncDomainService $lingoSyncDomainService,
        TranslatorInterface             $translator
    ): Response
    {
        if (!$this->isCsrfTokenValid(
            "start-lingo-sync-processing-{$request->get('videoId')}",
            $request->get('_csrf_token')
        )) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        $r = $this->verifyAndGetUserAndEntity(
            Video::class,
            $request->get('videoId'),
            AccessAttribute::Use
        );

        /** @var Video $video */
        $video = $r->getEntity();

        if ($lingoSyncDomainService->videoHasLingoSyncProcess($video)) {
            throw new BadRequestHttpException(
                "Video '{$video->getId()}' already has lingo sync process '{$lingoSyncDomainService->getProcessForVideo($video)->getId()}'."
            );
        }

        $lingoSyncDomainService->startProcessingVideo(
            $video,
            Bcp47LanguageCode::from(
                $request->get('bcp47LanguageCode')
            )
        );

        $this->addFlash(
            FlashMessageLabel::Success->value,
            $translator->trans(
                'processing_started',
                ['title' => $video->getTitle()],
                'videobasedmarketing.lingo_sync'
            )
        );

        return $this
            ->redirectToRoute(
                'videobasedmarketing.recordings.presentation.videos.overview',
                [
                    VideoFoldersController::VIDEO_FOLDER_ID_REQUEST_PARAM_NAME => $video->getVideoFolder()?->getId()
                ]
            );
    }
}
