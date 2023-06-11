<?php

namespace App\VideoBasedMarketing\LingoSync\Presentation\Controller;

use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\Shared\Domain\Enum\Gender;
use App\Shared\Infrastructure\Controller\AbstractController;
use App\Shared\Presentation\Enum\FlashMessageLabel;
use App\VideoBasedMarketing\Account\Domain\Enum\AccessAttribute;
use App\VideoBasedMarketing\LingoSync\Domain\Service\LingoSyncDomainService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Presentation\Controller\VideoFoldersController;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;


class LingoSyncProcessingController
    extends AbstractController
{
    /**
     * @throws Exception
     */
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
        Request                $request,
        LingoSyncDomainService $lingoSyncDomainService,
        TranslatorInterface    $translator
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

        if ($lingoSyncDomainService->videoHasRunningProcess($video)) {
            throw new BadRequestHttpException(
                "Video '{$video->getId()}' has running lingo sync processes."
            );
        }

        $lingoSyncDomainService->startLingoSyncProcess(
            $video,
            Bcp47LanguageCode::from(
                $request->get('originalLanguage')
            ),
            Gender::from(
                $request->get('originalGender')
            ),
            [
                Bcp47LanguageCode::from(
                    $request->get('targetLanguage')
                )
            ]
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

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/videos/{}/lingo-sync-processings/',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/videos/{}/lingo-sync-verarbeitungen/',
        ],
        name        : 'videobasedmarketing.lingo_sync.presentation.processing.status',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function processingStatusAction(
        Request                $request,
        LingoSyncDomainService $lingoSyncDomainService
    ): Response
    {
        $r = $this->verifyAndGetUserAndEntity(
            Video::class,
            $request->get('videoId'),
            AccessAttribute::Use
        );

        /** @var Video $video */
        $video = $r->getEntity();



        return $this->render(
            '@videobasedmarketing.lingo_sync/status.html.twig',
            [
                'lingoSyncProcesses' => $lingoSyncDomainService->getProcessesForVideo($video),
            ]
        );
    }
}
