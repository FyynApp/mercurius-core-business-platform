<?php

namespace App\VideoBasedMarketing\Mailings\Presentation\Component;

use App\VideoBasedMarketing\Mailings\Domain\Entity\VideoMailing;
use App\VideoBasedMarketing\Mailings\Presentation\Form\Type\VideoMailingType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
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
        readonly private EntityManagerInterface $entityManager
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


    private function storeDataAndRebuildForm(): void
    {
        $this->entityManager->persist($this->videoMailing);
        $this->entityManager->flush();
        $form = $this->createForm(VideoMailingType::class, $this->videoMailing);
        $this->formView = $form->createView();
        $this->formValues = $this->extractFormValues(
            $this->instantiateForm()
                ->createView()
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
