<?php

namespace App\Components\Feature\Recordings;

use App\Entity\Feature\Recordings\Video;
use App\Form\Type\Feature\Recordings\VideoType;
use App\Security\VotingAttribute;
use App\Service\Aspect\ShortId\ShortIdService;
use App\Service\Feature\Presentationpages\PresentationpagesService;
use App\Service\Feature\Recordings\VideoService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
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

    private bool $editModalIsOpen = false;

    private bool $shareModalIsOpen = false;

    private bool $deleteModalIsOpen = false;

    private string $shareUrl = '';

    private LoggerInterface $logger;

    private EntityManagerInterface $entityManager;

    private PresentationpagesService $presentationpagesService;

    private VideoService $videoService;

    private RouterInterface $router;

    private ShortIdService $shortIdService;

    public function __construct(
        LoggerInterface          $logger,
        EntityManagerInterface   $entityManager,
        VideoService             $videoService,
        PresentationpagesService $presentationpagesService,
        RouterInterface          $router,
        ShortIdService           $shortIdService
    )
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->videoService = $videoService;
        $this->presentationpagesService = $presentationpagesService;
        $this->router = $router;
        $this->shortIdService = $shortIdService;
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

        $this->switchShareUrlDirectLink();
    }

    #[LiveAction]
    public function save(): void
    {
        $this->submitForm();
        $this->storeDataAndRebuildForm();
    }

    
    #[LiveAction]
    public function getEditModalIsOpen(): bool
    {
        return $this->editModalIsOpen;
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
    public function getShareModalIsOpen(): bool
    {
        return $this->shareModalIsOpen;
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


    #[LiveAction]
    public function getDeleteModalIsOpen(): bool
    {
        return $this->deleteModalIsOpen;
    }

    #[LiveAction]
    public function showDeleteModal(): void
    {
        $this->deleteModalIsOpen = true;
    }

    #[LiveAction]
    public function hideDeleteModal(): void
    {
        $this->deleteModalIsOpen = false;
    }

    
    #[LiveAction]
    public function switchShareUrlDirectLink(): void
    {
        $this->shareUrl =
            $this->router->generate(
                'feature.recordings.video.share_link',
                ['videoShortId' => $this->shortIdService->encodeObjectId($this->video)],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
    }

    #[LiveAction]
    public function shareUrlFacebook(): string
    {
        return
            'https://www.facebook.com/sharer/sharer.php?u='
            . urlencode(
                $this->router->generate(
                    'feature.recordings.video.share_link',
                    ['videoShortId' => $this->shortIdService->encodeObjectId($this->video)],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            );
    }

    #[LiveAction]
    public function switchShareUrlInstagram(): void
    {
        $this->switchShareUrlDirectLink();
    }

    #[LiveAction]
    public function shareUrlTwitter(): string
    {
        return
            'https://twitter.com/intent/tweet?url='
            . urlencode(
                $this->router->generate(
                    'feature.recordings.video.share_link',
                    ['videoShortId' => $this->shortIdService->encodeObjectId($this->video)],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            )
            . '&text='
            . urlencode("Sieh' dir mein neues Video an!\n\n");
    }

    #[LiveAction]
    public function shareUrlLinkedIn(): string
    {
        return
            'https://www.linkedin.com/sharing/share-offsite/?url='
            . urlencode(
                $this->router->generate(
                    'feature.recordings.video.share_link',
                    ['videoShortId' => $this->shortIdService->encodeObjectId($this->video)],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            );
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
