<?php

namespace App\Components\Feature\Recordings;

use App\Entity\Feature\Recordings\Video;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    'feature_recordings_video_player_embedded',
    'feature/recordings/video_player_embedded_live_component.html.twig'
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
