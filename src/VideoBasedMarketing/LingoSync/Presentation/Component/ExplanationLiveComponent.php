<?php

namespace App\VideoBasedMarketing\LingoSync\Presentation\Component;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    'videobasedmarketing_lingo_sync_explanation',
    '@videobasedmarketing.lingo_sync/explanation_live_component.html.twig'
)]
class ExplanationLiveComponent
    extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp(writable: false)]
    public int $slide = 1;

    #[LiveProp(writable: false)]
    public int $maxSlides = 12;

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
