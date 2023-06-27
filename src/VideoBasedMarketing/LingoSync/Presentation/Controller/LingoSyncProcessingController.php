<?php

namespace App\VideoBasedMarketing\LingoSync\Presentation\Controller;

use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\Shared\Domain\Enum\Gender;
use App\Shared\Infrastructure\Controller\AbstractController;
use App\Shared\Presentation\Enum\FlashMessageLabel;
use App\VideoBasedMarketing\Account\Domain\Enum\AccessAttribute;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\LingoSync\Domain\Entity\LingoSyncProcess;
use App\VideoBasedMarketing\LingoSync\Domain\Service\LingoSyncCreditsDomainService;
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
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/lingo-sync-processes/',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/lingo-sync-prozesse/',
        ],
        name        : 'videobasedmarketing.lingo_sync.presentation.process.start',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function startProcessAction(
        Request                       $request,
        LingoSyncDomainService        $lingoSyncDomainService,
        LingoSyncCreditsDomainService $lingoSyncCreditsDomainService,
        TranslatorInterface           $translator,
        CapabilitiesService           $capabilitiesService
    ): Response
    {
        if (!$this->isCsrfTokenValid(
            "start-lingo-sync-process-{$request->get('videoId')}",
            $request->get('_csrf_token')
        )) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        $r = $this->verifyAndGetUserAndEntity(
            Video::class,
            $request->get('videoId'),
            AccessAttribute::Use
        );

        if (!$capabilitiesService->canTranslateVideos($r->getUser())) {
            throw $this->createAccessDeniedException();
        }

        /** @var Video $video */
        $video = $r->getEntity();

        if ($lingoSyncDomainService->videoHasRunningProcesses($video)) {
            throw new BadRequestHttpException(
                "Video '{$video->getId()}' has running lingo sync processes."
            );
        }

        if (!$lingoSyncCreditsDomainService->organizationHasEnoughAvailableCreditsForVideo($video)) {
            $this->addFlash(
                FlashMessageLabel::Warning->value,
                $translator->trans(
                    'no_remaining_translation_seconds',
                    [],
                    'videobasedmarketing.lingo_sync'
                )
            );

            if ($capabilitiesService->canPurchasePackages($r->getUser())) {
                return $this
                    ->redirectToRoute(
                        'videobasedmarketing.lingo_sync.presentation.buy_credits'
                    );
            } else {
                return $this
                    ->redirectToRoute(
                        'videobasedmarketing.recordings.presentation.videos.overview',
                        [
                            VideoFoldersController::VIDEO_FOLDER_ID_REQUEST_PARAM_NAME =>
                                $video->getVideoFolder()?->getId()
                        ]
                    );
            }
        }

        $lingoSyncDomainService->startProcess(
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
                'process_started',
                ['title' => $video->getTitle()],
                'videobasedmarketing.lingo_sync'
            )
        );

        return $this
            ->redirectToRoute(
                'videobasedmarketing.recordings.presentation.videos.overview',
                [
                    VideoFoldersController::VIDEO_FOLDER_ID_REQUEST_PARAM_NAME =>
                        $video->getVideoFolder()?->getId()
                ]
            );
    }

    /**
     * @throws Exception
     */
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/lingo-sync-processes/{lingoSyncProcessId}/restart',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/lingo-sync-prozesse/{lingoSyncProcessId}/neu-starten',
        ],
        name        : 'videobasedmarketing.lingo_sync.presentation.process.restart',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function restartProcessAction(
        string                        $lingoSyncProcessId,
        Request                       $request,
        LingoSyncDomainService        $lingoSyncDomainService,
        LingoSyncCreditsDomainService $lingoSyncCreditsDomainService,
        TranslatorInterface           $translator,
        CapabilitiesService           $capabilitiesService
    ): Response
    {
        if (!$this->isCsrfTokenValid(
            "restart-lingo-sync-process-{$lingoSyncProcessId}",
            $request->get('_csrf_token')
        )) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        $r = $this->verifyAndGetUserAndEntity(
            LingoSyncProcess::class,
            $lingoSyncProcessId,
            AccessAttribute::Use
        );

        /** @var LingoSyncProcess $lingoSyncProcess */
        $lingoSyncProcess = $r->getEntity();

        if (!$lingoSyncCreditsDomainService->organizationHasEnoughAvailableCreditsForVideo($lingoSyncProcess->getVideo())) {
            $this->addFlash(
                FlashMessageLabel::Warning->value,
                $translator->trans(
                    'no_remaining_translation_seconds',
                    [],
                    'videobasedmarketing.lingo_sync'
                )
            );

            if ($capabilitiesService->canPurchasePackages($r->getUser())) {
                return $this
                    ->redirectToRoute(
                        'videobasedmarketing.lingo_sync.presentation.buy_credits'
                    );
            } else {
                return $this
                    ->redirectToRoute(
                        'videobasedmarketing.recordings.presentation.videos.overview',
                        [
                            VideoFoldersController::VIDEO_FOLDER_ID_REQUEST_PARAM_NAME =>
                                $lingoSyncProcess->getVideo()->getVideoFolder()?->getId()
                        ]
                    );
            }
        }

        $lingoSyncDomainService->restartProcess($lingoSyncProcess);

        $this->addFlash(
            FlashMessageLabel::Success->value,
            $translator->trans(
                'process_restarted',
                [],
                'videobasedmarketing.lingo_sync'
            )
        );

        return $this
            ->redirectToRoute(
                'videobasedmarketing.lingo_sync.presentation.processes.status',
                [
                    'videoId' => $lingoSyncProcess->getVideo()->getId()
                ]
            );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/videos/{videoId}/lingo-sync-processes/',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/videos/{videoId}/lingo-sync-verarbeitungen/',
        ],
        name        : 'videobasedmarketing.lingo_sync.presentation.processes.status',
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
                'video'              => $video,
                'lingoSyncProcesses' => $lingoSyncDomainService->getProcessesForVideo($video),
            ]
        );
    }
}
