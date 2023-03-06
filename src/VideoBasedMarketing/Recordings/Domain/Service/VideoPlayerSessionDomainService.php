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

    /**
     * @throws \Doctrine\DBAL\Exception
     * @return float[]
     */
    public function getViewPercentagesPerSecond(
        Video $video
    ): array
    {
        if (is_null($video->getSeconds())) {
            return [];
        }

        /** @var ObjectRepository<VideoPlayerSession> $r */
        $r = $this->entityManager->getRepository(VideoPlayerSession::class);

        $sql = "
                SELECT COUNT(DISTINCT(s.id)) AS cnt
                FROM {$this->entityManager->getClassMetadata(VideoPlayerSession::class)->getTableName()} s
                INNER JOIN {$this->entityManager->getClassMetadata(VideoPlayerSessionEvent::class)->getTableName()} e
                ON s.id = e.video_player_sessions_id
                INNER JOIN {$this->entityManager->getClassMetadata(Video::class)->getTableName()} v
                ON v.id = s.videos_id
                WHERE
                    v.id = :vid
                    AND 
                    e.id IS NOT NULL
                ;
            ";
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $resultSet = $stmt->executeQuery([':vid' => $video->getId()]);

        $numberOfStartedVideoPlayerSessions = 0;
        foreach ($resultSet->fetchAllAssociative() as $row) {
            $numberOfStartedVideoPlayerSessions = $row['cnt'];
        }

        $result = [];
        for ($second = 0; $second < floor($video->getSeconds()); $second++) {

            if ($numberOfStartedVideoPlayerSessions === 0) {
                $result[$second] = 0;
                continue;
            }

            $sql = "
                SELECT COUNT(DISTINCT(s.id)) AS cnt
                FROM {$this->entityManager->getClassMetadata(VideoPlayerSessionEvent::class)->getTableName()} e
                INNER JOIN {$this->entityManager->getClassMetadata(VideoPlayerSession::class)->getTableName()} s
                ON s.id = e.video_player_sessions_id
                INNER JOIN {$this->entityManager->getClassMetadata(Video::class)->getTableName()} v
                ON v.id = s.videos_id
                WHERE
                    v.id = :vid
                    AND
                    e.player_current_time >= $second
                    AND
                    e.player_current_time < $second + 1
                    
                ;
            ";

            $stmt = $this->entityManager->getConnection()->prepare($sql);
            $resultSet = $stmt->executeQuery([':vid' => $video->getId()]);

            foreach ($resultSet->fetchAllAssociative() as $row) {
                $result[$second] = 100 / $numberOfStartedVideoPlayerSessions * $row['cnt'];
            }
        }

        return $result;
    }
}
