<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Component;

use App\VideoBasedMarketing\Presentationpages\Domain\Service\PresentationpagesService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Presentation\Service\RecordingsPresentationService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    'videobasedmarketing_recordings_video_share_widget',
    '@videobasedmarketing.recordings/video_share_widget_live_component.html.twig'
)]
class VideoShareWidgetLiveComponent
    extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp(fieldName: 'data')]
    public ?Video $video = null;

    #[LiveProp(writable: true)]
    public bool $shareModalIsOpen = false;

    #[LiveProp(writable: true)]
    public string $shareUrl = '';


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
        Video $video
    )
    {
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

        $this->switchShareUrlDirectLink();
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
                    'video_share_widget.twitter_note',
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
}
