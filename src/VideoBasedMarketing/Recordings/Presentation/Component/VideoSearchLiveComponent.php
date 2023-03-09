<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Component;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoFolder;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoSearchResultset;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoSearchDomainService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    'videobasedmarketing_recordings_video_search',
    '@videobasedmarketing.recordings/video_search_live_component.html.twig'
)]
class VideoSearchLiveComponent
    extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?VideoFolder $videoFolder = null;

    #[LiveProp]
    public ?VideoSearchResultset $resultset = null;

    #[LiveProp(writable: true)]
    public string $q = '';


    private LoggerInterface $logger;

    private VideoSearchDomainService $videoSearchDomainService;


    public function __construct(
        LoggerInterface          $logger,
        VideoSearchDomainService $videoSearchDomainService
    )
    {
        $this->logger = $logger;
        $this->videoSearchDomainService = $videoSearchDomainService;
    }

    public function mount()
    {
    }

    #[LiveAction]
    public function search(): void
    {
        /** @var User $user */
        $user = $this->getUser();

        $this->resultset = $this->videoSearchDomainService->findVideosByTitle(
            $this->q,
            $user->getCurrentlyActiveOrganization()
        );
    }
}
