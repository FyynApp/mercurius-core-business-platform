<?php

namespace App\Components\Feature\Recordings;

use App\Entity\Feature\Recordings\Video;
use App\Form\Type\Feature\Recordings\VideoType;
use App\Service\Feature\Recordings\VideoService;
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
    'feature_recordings_video_edit_form',
    'feature/recordings/video_edit_form_live_component.html.twig'
)]
class VideoEditFormLiveComponent extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;


    #[LiveProp(fieldName: 'data')]
    public ?Video $video = null;

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

    public function mount(?Video $video = null)
    {
        $this->video = $video;
    }

    #[LiveAction]
    public function save(): void
    {
        $this->submitForm();
        $this->storeDataAndRebuildForm();
    }

    #[LiveAction]
    public function setTitle(#[LiveArg] string $titleValue): void
    {
        $this->submitForm();
        $this->video->setTitle($titleValue);
        $this->storeDataAndRebuildForm();
    }


    private function storeDataAndRebuildForm(): void
    {
        $this->entityManager->persist($this->video);
        $this->entityManager->flush();
        $form = $this->createForm(VideoType::class, $this->video);
        $this->formView = $form->createView();
        $this->formValues = $this->extractFormValues(
            $this->instantiateForm()
                 ->createView()
        );
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            VideoType::class,
            $this->video
        );
    }
}
