<?php

namespace App\VideoBasedMarketing\Mailings\Presentation\Component;

use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    'videobasedmarketing_mailings_video_mailing_editor',
    '@videobasedmarketing.mailings/video_mailing_editor_live_component.html.twig'
)]
class VideoMailingEditorLiveComponent
    extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public Video $video;
}
