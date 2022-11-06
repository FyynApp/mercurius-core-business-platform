<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Component;

use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    'videobasedmarketing_recordings_video_player_embedded',
    '@videobasedmarketing.recordings/video_player_embedded_live_component.html.twig'
)]
class VideoPlayerEmbeddedLiveComponent
    extends AbstractController
{
    use DefaultActionTrait;


    #[LiveProp]
    public ?Video $video = null;

    public function mount(?Video $video = null)
    {
        $this->video = $video;
    }
}
