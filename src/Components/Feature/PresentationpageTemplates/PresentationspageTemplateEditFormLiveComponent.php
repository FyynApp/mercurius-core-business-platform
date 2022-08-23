<?php

namespace App\Components\Feature\PresentationpageTemplates;

use App\Entity\Feature\PresentationpageTemplates\PresentationpageTemplate;
use App\Entity\Feature\PresentationpageTemplates\PresentationpageTemplateElement;
use App\Entity\Feature\PresentationpageTemplates\PresentationpageTemplateElementVariant;
use App\Form\Type\Feature\PresentationpageTemplates\PresentationpageTemplateElementType;
use App\Form\Type\Feature\PresentationpageTemplates\PresentationpageTemplateType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use ReflectionEnum;
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
    public function addElement(#[LiveArg] string $variant)
    {
        $element = new PresentationpageTemplateElement();
        $element->setPresentationpageTemplate($this->presentationpageTemplate);
        $element->setPosition(sizeof($this->presentationpageTemplate->getPresentationpageTemplateElements()));
        $element->setElementVariant(PresentationpageTemplateElementVariant::tryFrom($variant));
        $this->presentationpageTemplate->addPresentationpageTemplateElement($element);
        $this->entityManager->persist($element);
        $this->entityManager->persist($this->presentationpageTemplate);
        $this->entityManager->flush();
        $this->formValues['presentationpageTemplateElements'][] = [];
    }

    #[LiveAction]
    public function removeElement(#[LiveArg] int $index, #[LiveArg] string $elementId)
    {
        foreach ($this->presentationpageTemplate->getPresentationpageTemplateElements() as $element) {
            if ($element->getId() === $elementId) {
                $this->presentationpageTemplate->removePresentationpageTemplateElement($element);
                $this->entityManager->persist($this->presentationpageTemplate);
                $this->entityManager->remove($element);
                $this->entityManager->flush();
                break;
            }
        }
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
