<?php

namespace App\Components\Feature\PresentationpageTemplates;

use App\Entity\Feature\PresentationpageTemplates\PresentationpageTemplate;
use App\Form\Type\Feature\PresentationpageTemplates\PresentationpageTemplateType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('feature_presentationpages_edit_form', 'feature/presentationpages/edit_form_live_component.html.twig')]
class PresentationspageTemplateEditFormLiveComponent extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp(fieldName: 'data')]
    public ?PresentationpageTemplate $presentationpageTemplate = null;

    private LoggerInterface $logger;

    private EntityManagerInterface $entityManager;


    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager
    )
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    public function mount(
        ?PresentationpageTemplate $presentationpageTemplate = null,
    ) {
        $this->presentationpageTemplate = $presentationpageTemplate;
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            PresentationpageTemplateType::class,
            $this->presentationpageTemplate
        );
    }
}
