<?php

namespace App\Components\Feature\Recordings;

use App\Entity\Feature\Recordings\Video;
use App\Form\Type\Feature\Recordings\VideoType;
use App\Security\VotingAttribute;
use App\Service\Feature\Presentationpages\PresentationpagesService;
use App\Service\Feature\Recordings\VideoService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    'feature_recordings_video_manage_widget',
    'feature/recordings/video_manage_widget_live_component.html.twig'
)]
class VideoManageWidgetLiveComponent extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;


    #[LiveProp(fieldName: 'data')]
    public ?Video $video = null;

    #[LiveProp]
    public bool $editModalIsOpen = false;

    #[LiveProp]
    public bool $shareModalIsOpen = false;

    private LoggerInterface $logger;

    private EntityManagerInterface $entityManager;

    private PresentationpagesService $presentationpagesService;

    public VideoService $videoService;


    public function __construct(
        LoggerInterface          $logger,
        EntityManagerInterface   $entityManager,
        VideoService             $videoService,
        PresentationpagesService $presentationpagesService
    )
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->videoService = $videoService;
        $this->presentationpagesService = $presentationpagesService;
    }

    /**
     * @throws Exception
     */
    public function mount(
        Video $video,
        bool $showEditModal
    )
    {
        $this->denyAccessUnlessGranted(VotingAttribute::Edit->value, $video);

        if (is_null($video->getVideoOnlyPresentationpageTemplate())) {
            $video->setVideoOnlyPresentationpageTemplate(
                $this->presentationpagesService->getVideoOnlyPresentationpageTemplatesForUser($video->getUser())[0]
            );
        }
        $this->video = $video;

        if ($showEditModal) {
            $this->showEditModal();
        }
    }

    #[LiveAction]
    public function save(): void
    {
        $this->submitForm();
        $this->storeDataAndRebuildForm();
    }

    #[LiveAction]
    public function showEditModal(): void
    {
        $this->editModalIsOpen = true;
    }

    #[LiveAction]
    public function hideEditModal(): void
    {
        $this->editModalIsOpen = false;
    }

    #[LiveAction]
    public function showShareModal(): void
    {
        $this->shareModalIsOpen = true;
    }

    #[LiveAction]
    public function hideShareModal(): void
    {
        $this->shareModalIsOpen = false;
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
