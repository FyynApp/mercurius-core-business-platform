<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Component;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Enum\VideosListViewMode;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoFolder;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoFinderResult;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoFinderResultset;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoDomainService;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoSearchDomainService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    'videobasedmarketing_recordings_videos_finder',
    '@videobasedmarketing.recordings/videos_finder_live_component.html.twig'
)]
class VideosFinderLiveComponent
    extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?VideoFolder $videoFolder = null;

    #[LiveProp(writable: true)]
    public string $q = '';

    #[LiveProp]
    public VideosListViewMode $videosListViewMode = VideosListViewMode::Tiles;


    private LoggerInterface $logger;

    private VideoDomainService $videoDomainService;

    private VideoSearchDomainService $videoSearchDomainService;

    private EntityManagerInterface $entityManager;


    public function __construct(
        LoggerInterface          $logger,
        VideoDomainService       $videoDomainService,
        VideoSearchDomainService $videoSearchDomainService,
        EntityManagerInterface   $entityManager
    )
    {
        $this->logger = $logger;
        $this->videoDomainService = $videoDomainService;
        $this->videoSearchDomainService = $videoSearchDomainService;
        $this->entityManager = $entityManager;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function mount(
        ?VideoFolder $videoFolder,
        string       $q
    ): void
    {
        $this->videoFolder = $videoFolder;

        $this->q = $q;

        /** @var User $user */
        $user = $this->getUser();

        $this->videosListViewMode = $user->getVideosListViewMode();
    }

    #[LiveAction]
    public function getFinderResults(): array
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->getResultset($user)->getResults();
    }

    #[LiveAction]
    public function search(): void
    {
        return;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    private function getResultset(
        User $user
    ): VideoFinderResultset
    {
        if (trim($this->q) === '') {
            $results = [];
            $videos = $this->videoDomainService
                ->getAvailableVideosForCurrentlyActiveOrganization(
                    $user,
                    $this->videoFolder
                );
            foreach ($videos as $video) {
                $results[] = new VideoFinderResult($video);
            }

            return new VideoFinderResultset($results);
        } else {
            return $this->videoSearchDomainService->findVideosByTitle(
                $user,
                $this->q,
                $user->getCurrentlyActiveOrganization()
            );
        }
    }
}
