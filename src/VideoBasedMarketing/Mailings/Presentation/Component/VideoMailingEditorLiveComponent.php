<?php

namespace App\VideoBasedMarketing\Mailings\Presentation\Component;

use App\VideoBasedMarketing\Mailings\Domain\Entity\VideoMailing;
use App\VideoBasedMarketing\Mailings\Infrastructure\SymfonyMessage\ImproveVideoMailingBodyAboveVideoCommandSymfonyMessage;
use App\VideoBasedMarketing\Mailings\Presentation\Form\Type\VideoMailingType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    'videobasedmarketing_mailings_video_mailing_editor',
    '@videobasedmarketing.mailings/video_mailing_editor_live_component.html.twig'
)]
class VideoMailingEditorLiveComponent
    extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;


    public function __construct(
        readonly private EntityManagerInterface $entityManager,
        readonly private MessageBusInterface    $messageBus
    ) {

    }

    #[LiveProp(fieldName: 'data')]
    public VideoMailing $videoMailing;

    #[LiveAction]
    public function save(): void
    {
        if (is_null($this->formView)) {
            $this->submitForm();
        }
        $this->storeDataAndRebuildForm();
    }


    #[LiveAction]
    public function improveTexts(): void
    {
        $this->save();
        $this
            ->messageBus
            ->dispatch(
                new ImproveVideoMailingBodyAboveVideoCommandSymfonyMessage(
                    $this->videoMailing
                )
            );
        $this->videoMailing->setImprovedBodyAboveVideoIsCurrentlyBeingGenerated(true);
        $this->storeDataAndRebuildForm();
    }

    #[LiveAction]
    public function useImprovedBodyAboveVideoText(): void
    {
        $this->save();
        $this->videoMailing->setBodyAboveVideo(
            $this->videoMailing->getImprovedBodyAboveVideo()
        );
        $this->videoMailing->setImprovedBodyAboveVideo('');
        $this->storeDataAndRebuildForm();
    }

    #[LiveAction]
    public function resetImprovedBodyAboveVideoText(): void
    {
        $this->save();
        $this->videoMailing->setImprovedBodyAboveVideo('');
        $this->storeDataAndRebuildForm();
    }

    private function storeDataAndRebuildForm(): void
    {
        $this->entityManager->persist($this->videoMailing);
        $this->entityManager->flush();
        $form = $this->createForm(VideoMailingType::class, $this->videoMailing);
        $this->formView = $form->createView();
        $this->formValues = $this->extractFormValues(
            $this->instantiateForm()->createView()
        );
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            VideoMailingType::class,
            $this->videoMailing
        );
    }
}
