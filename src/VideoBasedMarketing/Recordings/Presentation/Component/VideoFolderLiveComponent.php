<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Component;

use App\VideoBasedMarketing\Account\Domain\Enum\AccessAttribute;
use App\VideoBasedMarketing\Account\Domain\Service\AccessService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoFolder;
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
    'videobasedmarketing_recordings_video_folder',
    '@videobasedmarketing.recordings/video_folder_live_component.html.twig'
)]
class VideoFolderLiveComponent
    extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?VideoFolder $videoFolder = null;

    #[LiveProp]
    public int $index = 0;

    #[LiveProp(writable: true)]
    public string $name = '';

    #[LiveProp]
    public bool $nameIsBeingEdited = false;

    #[LiveProp]
    public bool $deleteModalIsOpen = false;


    private LoggerInterface $logger;

    private EntityManagerInterface $entityManager;

    private AccessService $accessService;

    private TranslatorInterface $translator;


    public function __construct(
        LoggerInterface        $logger,
        EntityManagerInterface $entityManager,
        AccessService          $accessService,
        TranslatorInterface    $translator
    )
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->accessService = $accessService;
        $this->translator = $translator;
    }

    /**
     * @throws Exception
     */
    public function mount(
        VideoFolder $videoFolder,
        int         $index
    )
    {
        $this->videoFolder = $videoFolder;
        $this->index = $index;
        $this->name = $videoFolder->getName();
    }

    #[LiveAction]
    public function startEditingName(): void
    {
        $this->nameIsBeingEdited = true;
    }

    #[LiveAction]
    public function stopEditingName(): void
    {
        if (trim($this->name) !== '') {
            if (!$this->accessService->userCanAccessEntity(
                $this->getUser(),
                AccessAttribute::Edit,
                $this->videoFolder
            )) {
                throw $this->createAccessDeniedException('No access.');
            }

            $this->videoFolder->setName($this->name);
            $this->entityManager->persist($this->videoFolder);
            $this->entityManager->flush();
            $this->nameIsBeingEdited = false;
        }
    }

    #[LiveAction]
    public function openDeleteModal(): void
    {
        $this->deleteModalIsOpen = true;
    }

    #[LiveAction]
    public function closeDeleteModal(): void
    {
        $this->deleteModalIsOpen = false;
    }
}
