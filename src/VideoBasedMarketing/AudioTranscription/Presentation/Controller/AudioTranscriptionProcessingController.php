<?php

namespace App\VideoBasedMarketing\AudioTranscription\Presentation\Controller;


use App\Shared\Infrastructure\Controller\AbstractController;
use App\Shared\Presentation\Enum\FlashMessageLabel;
use App\VideoBasedMarketing\Account\Domain\Enum\AccessAttribute;
use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\AudioTranscription\Domain\Service\AudioTranscriptionDomainService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Presentation\Controller\VideoFoldersController;
use Doctrine\DBAL\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class AudioTranscriptionProcessingController
    extends AbstractController
{
    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/audio-transcriptions/',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/audio-abschriften/',
        ],
        name        : 'videobasedmarketing.audio_transcription.presentation.processing.start',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function startProcessingAction(
        Request                         $request,
        AudioTranscriptionDomainService $audioTranscriptionDomainService,
        TranslatorInterface             $translator,
        CapabilitiesService             $capabilitiesService
    ): Response
    {
        if (!$this->isCsrfTokenValid(
            "start-audio-transcription-{$request->get('videoId')}",
            $request->get('_csrf_token')
        )) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        $r = $this->verifyAndGetUserAndEntity(
            Video::class,
            $request->get('videoId'),
            AccessAttribute::Use
        );

        if (!$capabilitiesService->canManuallyStartAudioTranscription($r->getUser())) {
            throw new BadRequestHttpException(
                "User '{$r->getUser()->getId()}' is not allowed to manually start audio transcription."
            );
        }

        /** @var Video $video */
        $video = $r->getEntity();

        if (!is_null($audioTranscriptionDomainService->getAudioTranscription($video))) {
            throw new BadRequestHttpException(
                "Video '{$video->getId()}' already has audio transcription '{$audioTranscriptionDomainService->getAudioTranscription($video)->getId()}'."
            );
        }

        $audioTranscriptionDomainService->startProcessingVideo(
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
                'videobasedmarketing.audio_transcription'
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
