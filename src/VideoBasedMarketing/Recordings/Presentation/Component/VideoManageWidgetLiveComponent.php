<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Component;

use App\VideoBasedMarketing\Account\Domain\Enum\VotingAttribute;
use App\VideoBasedMarketing\Presentationpages\Domain\Service\PresentationpagesService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Presentation\Form\Type\VideoType;
use App\VideoBasedMarketing\Recordings\Presentation\Service\RecordingsPresentationService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
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

    #[LiveProp(writable: false)]
    public bool $shareModalIsOpen = false;

    #[LiveProp(writable: false)]
    public bool $deleteModalIsOpen = false;

    #[LiveProp(writable: true)]
    public bool $titleIsEdited = false;

    #[LiveProp(writable: true)]
    public string $shareUrl = '';

    #[LiveProp]
    public bool $doneCtaMustRedirectToOverview = false;


    private LoggerInterface $logger;

    private EntityManagerInterface $entityManager;

    private PresentationpagesService $presentationpagesService;

    private TranslatorInterface $translator;

    private RecordingsPresentationService $recordingsPresentationService;


    public function __construct(
        LoggerInterface               $logger,
        EntityManagerInterface        $entityManager,
        PresentationpagesService      $presentationpagesService,
        TranslatorInterface           $translator,
        RecordingsPresentationService $recordingsPresentationService
    )
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->presentationpagesService = $presentationpagesService;
        $this->translator = $translator;
        $this->recordingsPresentationService = $recordingsPresentationService;
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
        $this->stopEditingTitle();
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
    public function startEditingTitle(): void
    {
        $this->titleIsEdited = true;
    }

    #[LiveAction]
    public function stopEditingTitle(): void
    {
        $this->titleIsEdited = false;
    }


    /**
     * @throws Exception
     */
    #[LiveAction]
    public function switchShareUrlDirectLink(): void
    {
        $this->shareUrl = $this
            ->recordingsPresentationService
            ->getVideoShareLinkUrl($this->video);
    }

    /**
     * @throws Exception
     */
    #[LiveAction]
    public function shareUrlFacebook(): string
    {
        return
            'https://www.facebook.com/sharer/sharer.php?u='
            . urlencode(
                $this
                    ->recordingsPresentationService
                    ->getVideoShareLinkUrl($this->video)
            );
    }

    /**
     * @throws Exception
     */
    #[LiveAction]
    public function switchShareUrlInstagram(): void
    {
        $this->switchShareUrlDirectLink();
    }

    /**
     * @throws Exception
     */
    #[LiveAction]
    public function shareUrlTwitter(): string
    {
        return
            'https://twitter.com/intent/tweet?url='
            . urlencode(
                $this
                    ->recordingsPresentationService
                    ->getVideoShareLinkUrl($this->video)
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

    /**
     * @throws Exception
     */
    #[LiveAction]
    public function shareUrlLinkedIn(): string
    {
        return
            'https://www.linkedin.com/sharing/share-offsite/?url='
            . urlencode(
                $this
                    ->recordingsPresentationService
                    ->getVideoShareLinkUrl($this->video)
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
