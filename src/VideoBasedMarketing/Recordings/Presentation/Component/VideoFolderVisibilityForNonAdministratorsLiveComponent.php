<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Component;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoFolder;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoFolderDomainService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    'videobasedmarketing_recordings_video_folder_visibility_for_non_administrators_live_component',
    '@videobasedmarketing.recordings/video_folder_visibility_for_non_administrators_live_component.html.twig'
)]
class VideoFolderVisibilityForNonAdministratorsLiveComponent
    extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?VideoFolder $videoFolder = null;


    private LoggerInterface $logger;

    private EntityManagerInterface $entityManager;

    private CapabilitiesService $capabilitiesService;

    private VideoFolderDomainService $videoFolderDomainService;


    public function __construct(
        LoggerInterface          $logger,
        EntityManagerInterface   $entityManager,
        CapabilitiesService      $capabilitiesService,
        VideoFolderDomainService $videoFolderDomainService
    )
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->capabilitiesService = $capabilitiesService;
        $this->videoFolderDomainService = $videoFolderDomainService;
    }

    /**
     * @throws Exception
     */
    public function mount(
        VideoFolder $videoFolder
    ): void
    {
        $this->videoFolder = $videoFolder;
    }

    #[LiveAction]
    public function switch(): void
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->capabilitiesService->canEditFolderVisibilityForNonAdministrators($user)) {
            $this->videoFolderDomainService->setIsVisibleForNonAdministrators(
                $this->videoFolder,
                !$this->videoFolder->getIsVisibleForNonAdministrators()
            );
        }
    }
}
