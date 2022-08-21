<?php

namespace App\Components\Feature\PresentationpageTemplates;

use App\Entity\Feature\PresentationpageTemplates\PresentationpageTemplate;
use App\Form\Type\Feature\PresentationpageTemplates\PresentationpageTemplateType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    'feature_presentationpage_templates_edit_form',
    'feature/presentationpage_templates/edit_form_live_component.html.twig'
)]
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

    public function mount(?PresentationpageTemplate $presentationpageTemplate = null)
    {
        $this->presentationpageTemplate = $presentationpageTemplate;
    }

    #[LiveAction]
    public function addElement()
    {
        $this->formValues['presentationpageTemplateElements'][] = [];
    }

    #[LiveAction]
    public function removeComment(#[LiveArg] int $index)
    {
        unset($this->formValues['presentationpageTemplateElements'][$index]);
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            PresentationpageTemplateType::class,
            $this->presentationpageTemplate
        );
    }
}
