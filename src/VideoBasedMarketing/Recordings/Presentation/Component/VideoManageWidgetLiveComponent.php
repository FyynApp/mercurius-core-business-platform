<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Component;

use App\Shared\Infrastructure\Service\ShortIdService;
use App\VideoBasedMarketing\Account\Domain\Enum\VotingAttribute;
use App\VideoBasedMarketing\Presentationpages\Domain\Service\PresentationpagesService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Presentation\Form\Type\VideoType;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    'videobasedmarketing_recordings_video_manage_widget',
    '@videobasedmarketing.recordings/video_manage_widget_live_component.html.twig'
)]
class VideoManageWidgetLiveComponent
    extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;


    #[LiveProp(fieldName: 'data')]
    public ?Video $video = null;

    #[LiveProp(writable: true)]
    public bool $editModalIsOpen = false;

    #[LiveProp(writable: true)]
    public bool $shareModalIsOpen = false;

    #[LiveProp(writable: true)]
    public bool $deleteModalIsOpen = false;

    #[LiveProp(writable: true)]
    public string $shareUrl = '';

    #[LiveProp]
    public bool $doneCtaMustRedirectToOverview = false;


    private LoggerInterface $logger;

    private EntityManagerInterface $entityManager;

    private PresentationpagesService $presentationpagesService;

    private RouterInterface $router;

    private ShortIdService $shortIdService;

    private TranslatorInterface $translator;


    public function __construct(
        LoggerInterface            $logger,
        EntityManagerInterface     $entityManager,
        PresentationpagesService   $presentationpagesService,
        RouterInterface            $router,
        ShortIdService             $shortIdService,
        TranslatorInterface        $translator
    )
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->presentationpagesService = $presentationpagesService;
        $this->router = $router;
        $this->shortIdService = $shortIdService;
        $this->translator = $translator;
    }

    /**
     * @throws Exception
     */
    public function mount(
        Video $video,
        bool $showEditModal,
        bool $doneCtaMustRedirectToOverview = false
    )
    {
        $this->denyAccessUnlessGranted(VotingAttribute::View->value, $video);

        if (is_null($video->getVideoOnlyPresentationpageTemplate())) {
            $video->setVideoOnlyPresentationpageTemplate(
                $this
                    ->presentationpagesService
                    ->getVideoOnlyPresentationpageTemplatesForUser(
                        $video->getUser()
                    )[0]
            );
        }
        $this->video = $video;

        if ($showEditModal) {
            $this->showEditModal();
        }

        $this->doneCtaMustRedirectToOverview = $doneCtaMustRedirectToOverview;

        $this->switchShareUrlDirectLink();
    }

    #[LiveAction]
    public function save(): void
    {
        if (is_null($this->formView)) {
            $this->submitForm();
        }
        $this->storeDataAndRebuildForm();
    }

    
    #[LiveAction]
    public function showEditModal(): void
    {
        $this->denyAccessUnlessGranted(VotingAttribute::View->value, $this->video);
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
                'videobasedmarketing.recordings.presentation.video.share_link',
                ['videoShortId' => $this->shortIdService->encodeObject($this->video)],
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
                    'videobasedmarketing.recordings.presentation.video.share_link',
                    ['videoShortId' => $this->shortIdService->encodeObject($this->video)],
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
                    'videobasedmarketing.recordings.presentation.video.share_link',
                    ['videoShortId' => $this->shortIdService->encodeObject($this->video)],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            )
            . '&text='
            . urlencode("{$this
                ->translator
                ->trans(
                    'video_manage_widget.share_modal.twitter_note',
                    [],
                    'videobasedmarketing.recordings'
                )}\n\n");
    }

    #[LiveAction]
    public function shareUrlLinkedIn(): string
    {
        return
            'https://www.linkedin.com/sharing/share-offsite/?url='
            . urlencode(
                $this->router->generate(
                    'videobasedmarketing.recordings.presentation.video.share_link',
                    ['videoShortId' => $this->shortIdService->encodeObject($this->video)],
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
