<?php

namespace App\Components\Feature\Presentationpages;

use App\Entity\Feature\Presentationpages\Presentationpage;
use App\Form\Type\Feature\Presentationpages\PresentationpageType;
use App\Service\Feature\Recordings\VideoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('feature_presentationpages_edit_form', 'feature/presentationpages/edit_form_live_component.html.twig')]
class PresentationspageEditFormLiveComponent extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp(fieldName: 'data')]
    public ?Presentationpage $presentationpage = null;

    #[LiveProp]
    public string $posterAnimatedAssetUrl = '';

    private VideoService $videoService;

    public function __construct(VideoService $videoService)
    {
        $this->videoService = $videoService;
    }

    protected function instantiateForm(): FormInterface
    {
        $this->posterAnimatedAssetUrl = $this->videoService->getPosterAnimatedAssetUrl($this->presentationpage->getVideo());
        return $this->createForm(PresentationpageType::class, $this->presentationpage);
    }
}
