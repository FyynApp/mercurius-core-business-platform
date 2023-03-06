<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoFolder;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoPlayerSession;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoPlayerSessionEvent;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use ValueError;


readonly class VideoPlayerSessionDomainService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    )
    {
    }

    /**
     * @throws Exception
     */
    public function createVideoPlayerSession(
        ?User  $viewingUser,
        Video  $video,
        string $ipAddress
    ): ?VideoPlayerSession
    {
        if (!is_null($viewingUser)) {
            if ($viewingUser->getCurrentlyActiveOrganization()->getId()
                === $video->getOrganization()->getId()
            ) {
                // we only want to track player sessions from outside the org
                return null;
            }
        }

        $videoPlayerSession = new VideoPlayerSession(
            $video,
            $ipAddress
        );
        $this->entityManager->persist($videoPlayerSession);
        $this->entityManager->flush();

        return $videoPlayerSession;
    }

    /**
     * @throws Exception
     */
    public function trackEvent(
        VideoPlayerSession $session,
        float              $playerCurrentTime
    ): void
    {
        $event = new VideoPlayerSessionEvent(
            $session,
            $playerCurrentTime
        );

        $this->entityManager->persist($event);
        $this->entityManager->flush();
    }
}
