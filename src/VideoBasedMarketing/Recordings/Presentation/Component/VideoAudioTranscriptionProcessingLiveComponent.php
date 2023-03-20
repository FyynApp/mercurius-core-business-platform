<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Component;

use App\VideoBasedMarketing\Account\Domain\Enum\AccessAttribute;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscription;
use App\VideoBasedMarketing\AudioTranscription\Domain\Service\AudioTranscriptionDomainService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    'videobasedmarketing_recordings_video_audio_transcription_processing',
    '@videobasedmarketing.recordings/video_audio_transcription_processing_live_component.html.twig'
)]
class VideoAudioTranscriptionProcessingLiveComponent
    extends AbstractController
{
    use DefaultActionTrait;

    private AudioTranscriptionDomainService $audioTranscriptionDomainService;

    public function __construct(
        AudioTranscriptionDomainService $audioTranscriptionDomainService
    )
    {
        $this->audioTranscriptionDomainService = $audioTranscriptionDomainService;
    }


    #[LiveProp]
    public AudioTranscription $audioTranscription;

    public function stillRunning(): bool
    {
        $this->denyAccessUnlessGranted(
            AccessAttribute::View->value,
            $this->audioTranscription
        );

        return $this->audioTranscriptionDomainService->stillRunning(
            $this->audioTranscription
        );
    }
}
