<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Component;

use App\VideoBasedMarketing\Account\Domain\Enum\AccessAttribute;
use App\VideoBasedMarketing\LingoSync\Domain\Service\LingoSyncDomainService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    'videobasedmarketing_recordings_video_lingo_sync_processing',
    '@videobasedmarketing.recordings/video_lingo_sync_processing_live_component.html.twig'
)]
class VideoLingoSyncProcessingLiveComponent
    extends AbstractController
{
    use DefaultActionTrait;

    private LingoSyncDomainService $lingoSyncDomainService;

    public function __construct(
        LingoSyncDomainService $lingoSyncDomainService
    )
    {
        $this->lingoSyncDomainService = $lingoSyncDomainService;
        $this->video = null;
    }

    public function mount(
        Video $video
    ): void
    {
        $this->video = $video;
    }


    #[LiveProp]
    public ?Video $video;

    public function stillRunning(): bool
    {
        $this->denyAccessUnlessGranted(
            AccessAttribute::Use->value,
            $this->video
        );

        return $this->lingoSyncDomainService->videoHasRunningProcess(
            $this->video
        );
    }
}
