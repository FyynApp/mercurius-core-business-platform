<?php

namespace App\Components\Feature\Presentationpages;

use App\Entity\Feature\Presentationpages\Presentationpage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent('feature_presentationpages_presentationpage_screenshot', 'feature/presentationpages/presentationpage_screenshot_live_component.html.twig')]
class PresentationpageScreenshotLiveComponent extends AbstractController
{
    use DefaultActionTrait;


    #[LiveProp]
    public ?Presentationpage $presentationpage = null;

    public function mount(?Presentationpage $presentationpage = null)
    {
        $this->presentationpage = $presentationpage;
    }
}
