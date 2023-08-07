<?php

namespace App\VideoBasedMarketing\LingoSync\Presentation\Component;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    'videobasedmarketing_lingo_sync_prerequisites_info',
    '@videobasedmarketing.lingo_sync/prerequisites_info_live_component.html.twig'
)]
class PrerequisitesInfoLiveComponent
    extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp(writable: false)]
    public int $slide = 1;

    #[LiveProp(writable: false)]
    public int $maxSlides = 4;

    #[LiveAction]
    public function nextSlide(): void
    {
        $this->slide++;
    }

    #[LiveAction]
    public function previousSlide(): void
    {
        $this->slide--;
    }
}
