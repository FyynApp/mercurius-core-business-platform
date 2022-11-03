<?php

namespace App\Components\Feature\Presentationpages;

use App\Entity\Feature\Presentationpages\BgColor;
use App\Entity\Feature\Presentationpages\FgColor;
use App\Entity\Feature\Presentationpages\Presentationpage;
use App\Entity\Feature\Presentationpages\PresentationpageBackground;
use App\Entity\Feature\Presentationpages\PresentationpageElement;
use App\Entity\Feature\Presentationpages\PresentationpageElementHorizontalPosition;
use App\Entity\Feature\Presentationpages\PresentationpageElementVariant;
use App\Entity\Feature\Presentationpages\TextColor;
use App\Form\Type\Feature\Presentationpages\PresentationpageType;
use App\Service\Feature\Recordings\VideoService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    'feature_presentationpages_edit_form',
    'feature/presentationpages/edit_form_live_component.html.twig'
)]
class PresentationspageEditFormLiveComponent
    extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;


    #[LiveProp(fieldName: 'data')]
    public ?Presentationpage $presentationpage = null;

    private LoggerInterface $logger;

    private EntityManagerInterface $entityManager;

    public VideoService $videoService;


    public function __construct(
        LoggerInterface        $logger,
        EntityManagerInterface $entityManager,
        VideoService           $videoService
    )
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->videoService = $videoService;
    }

    public function mount(?Presentationpage $presentationpage = null)
    {
        $this->presentationpage = $presentationpage;
    }

    #[LiveAction]
    public function save(): void
    {
        $this->submitForm();
        $this->storeDataAndRebuildForm();
    }

    #[LiveAction]
    public function setBackground(#[LiveArg] string $backgroundValue): void
    {
        $this->submitForm();

        $this->presentationpage->setBackground(PresentationpageBackground::from($backgroundValue));

        $this->storeDataAndRebuildForm();
    }

    #[LiveAction]
    public function setBgColor(#[LiveArg] string $bgColorValue): void
    {
        $this->submitForm();

        $this->presentationpage->setBgColor(BgColor::from($bgColorValue));

        $this->storeDataAndRebuildForm();
    }

    #[LiveAction]
    public function setFgColor(#[LiveArg] string $fgColorValue): void
    {
        $this->submitForm();

        $this->presentationpage->setFgColor(FgColor::from($fgColorValue));

        $this->storeDataAndRebuildForm();
    }

    #[LiveAction]
    public function setTextColor(#[LiveArg] string $textColorValue): void
    {
        $this->submitForm();

        $this->presentationpage->setTextColor(TextColor::from($textColorValue));

        $this->storeDataAndRebuildForm();
    }

    #[LiveAction]
    public function addElement(#[LiveArg] string $variant): void
    {
        $resolvedVariant = PresentationpageElementVariant::tryFrom($variant);
        if (is_null($resolvedVariant)) {
            throw new BadRequestHttpException("Unknown variant '$variant'.");
        }

        $this->submitForm();

        $element = new PresentationpageElement(
            $resolvedVariant,
            sizeof($this->presentationpage->getPresentationpageElements())
        );
        $this->presentationpage->addPresentationpageElement($element);
        $this->entityManager->persist($element);

        $this->storeDataAndRebuildForm();
    }

    #[LiveAction]
    public function removeElement(#[LiveArg] string $elementId): void
    {
        $this->submitForm();

        foreach ($this->presentationpage->getPresentationpageElements() as $element) {
            if ($element->getId() === $elementId) {
                $this->logger->debug("Removing element with id $elementId");
                $this->presentationpage->removePresentationpageElement($element);
                $this->entityManager->remove($element);
                $this->entityManager->persist($this->presentationpage);
                $this->entityManager->flush();
                $this->entityManager->refresh($this->presentationpage);
                break;
            }
        }

        $i = 0;
        foreach ($this->presentationpage->getPresentationpageElements() as $element) {
            $element->setPosition($i);
            $this->entityManager->persist($element);
            $this->entityManager->flush();
            $i++;
        }

        $this->storeDataAndRebuildForm();
    }

    #[LiveAction]
    public function moveElementUp(
        #[LiveArg] string $elementId
    ): void
    {
        $elementToMove = $this->entityManager->find(PresentationpageElement::class, $elementId);
        if (is_null($elementToMove)) {
            throw new NotFoundHttpException("Could not find element with id '$elementId'.");
        }

        if ($elementToMove->getPosition() === 0) {
            throw new BadRequestHttpException("Element '$elementId' is already at the first position.");
        }

        $otherElement = null;
        foreach ($this->presentationpage->getPresentationpageElements() as $element) {
            if ($element->getPosition() === $elementToMove->getPosition() - 1) {
                $otherElement = $element;
                break;
            }
        }

        if (is_null($otherElement)) {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, 'Could not find element above the element to move.');
        }

        $this->submitForm();

        $this->swapPositionOfElements($elementToMove, $otherElement);
    }

    #[LiveAction]
    public function moveElementDown(
        #[LiveArg] string $elementId
    ): void
    {
        $elementToMove = $this->entityManager->find(PresentationpageElement::class, $elementId);
        if (is_null($elementToMove)) {
            throw new NotFoundHttpException("Could not find element with id '$elementId'.");
        }

        if ($elementToMove->getPosition() === $this->presentationpage->getPresentationpageElements()
                                                                     ->count() - 1) {
            throw new BadRequestHttpException("Element '$elementId' is already at the last position.");
        }

        $otherElement = null;
        foreach ($this->presentationpage->getPresentationpageElements() as $element) {
            if ($element->getPosition() === $elementToMove->getPosition() + 1) {
                $otherElement = $element;
                break;
            }
        }

        if (is_null($otherElement)) {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, 'Could not find element below the element to move.');
        }

        $this->submitForm();

        $this->swapPositionOfElements($elementToMove, $otherElement);
    }


    #[LiveAction]
    public function setElementHorizontalPosition(
        #[LiveArg] string $elementId,
        #[LiveArg] string $horizontalPosition
    ): void
    {
        $resolvedHorizontalPosition = PresentationpageElementHorizontalPosition::tryFrom($horizontalPosition);
        if (is_null($resolvedHorizontalPosition)) {
            throw new BadRequestHttpException("Unknown horizontal position '$horizontalPosition'.");
        }

        $element = $this->entityManager->find(PresentationpageElement::class, $elementId);
        if (is_null($element)) {
            throw new NotFoundHttpException("Could not find element with id '$elementId'.");
        }

        $this->submitForm();

        $element->setElementHorizontalPosition($resolvedHorizontalPosition);

        $this->entityManager->persist($element);
        $this->entityManager->flush();

        $this->storeDataAndRebuildForm();
    }


    private function swapPositionOfElements(
        PresentationpageElement $firstElement,
        PresentationpageElement $secondElement
    ): void
    {
        $firstElementPosition = $firstElement->getPosition();
        $secondElementPosition = $secondElement->getPosition();

        $firstElement->setPosition($secondElementPosition);
        $secondElement->setPosition($firstElementPosition);

        $this->entityManager->persist($firstElement);
        $this->entityManager->persist($secondElement);

        $this->entityManager->flush();

        $this->storeDataAndRebuildForm();
    }


    private function storeDataAndRebuildForm(): void
    {
        $this->entityManager->persist($this->presentationpage);
        $this->entityManager->flush();
        $form = $this->createForm(PresentationpageType::class, $this->presentationpage);
        $this->formView = $form->createView();
        $this->formValues = $this->extractFormValues(
            $this->instantiateForm()
                 ->createView()
        );
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            PresentationpageType::class,
            $this->presentationpage
        );
    }
}
