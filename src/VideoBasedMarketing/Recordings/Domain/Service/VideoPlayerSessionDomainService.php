<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoFolder;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoPlayerSession;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoPlayerSessionAnalyticsInfo;
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

        $numberOfStartedVideoPlayerSessions = $this
            ->getNumberOfStartedVideoPlayerSessions($video);

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
                    e.player_current_time < ($second + 1)
                ;
            ";

            $stmt = $this->entityManager->getConnection()->prepare($sql);
            $stmt->bindValue(':vid', $video->getId());
            $resultSet = $stmt->executeQuery();

            foreach ($resultSet->fetchAllAssociative() as $row) {
                $result[$second] = 100 / $numberOfStartedVideoPlayerSessions * $row['cnt'];
            }
        }

        return $result;
    }

    /**
     * @return VideoPlayerSessionAnalyticsInfo[]
     * @throws \Doctrine\DBAL\Exception
     */
    public function getVideoPlayerSessionAnalyticsInfos(
        Video $video
    ): array
    {
        $infos = [];
        $sql = "
                SELECT s.id AS id
                FROM {$this->entityManager->getClassMetadata(VideoPlayerSession::class)->getTableName()} s
                INNER JOIN {$this->entityManager->getClassMetadata(Video::class)->getTableName()} v
                ON v.id = s.videos_id
                WHERE
                    v.id = :vid
                ORDER BY s.created_at DESC
                LIMIT 100
                ;
            ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->bindValue(':vid', $video->getId());
        $resultSet = $stmt->executeQuery();

        foreach ($resultSet->fetchAllAssociative() as $sessionRow) {
            $session = $this->entityManager->find(VideoPlayerSession::class, $sessionRow['id']);


            $sql = "
                SELECT e.player_current_time AS playerCurrentTime
                FROM {$this->entityManager->getClassMetadata(VideoPlayerSessionEvent::class)->getTableName()} e
                INNER JOIN {$this->entityManager->getClassMetadata(VideoPlayerSession::class)->getTableName()} s
                ON s.id = e.video_player_sessions_id
                WHERE
                    s.id = :sid
                ORDER BY e.created_at DESC
                ;
            ";

            $stmt = $this->entityManager->getConnection()->prepare($sql);
            $stmt->bindValue(':sid', $session->getId());
            $resultSet = $stmt->executeQuery();

            $watchedSeconds = [];
            foreach ($resultSet->fetchAllAssociative() as $eventRow) {
                $watchedSeconds[] = (int)floor($eventRow['playerCurrentTime']) - 1;
            }

            $secondsToDidWatch = [];
            for ($i = 0; $i < (int)$video->getSeconds(); $i++) {
                if (in_array($i, $watchedSeconds)) {
                    $secondsToDidWatch[$i] = true;
                } else {
                    $secondsToDidWatch[$i] = false;
                }
            }

            $infos[] = new VideoPlayerSessionAnalyticsInfo(
                $session,
                $secondsToDidWatch
            );
        }

        return $infos;
    }


    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function getNumberOfVideoPlayerSessions(
        Video $video
    ): int
    {
        $sql = "
                SELECT COUNT(DISTINCT(s.id)) AS cnt
                FROM {$this->entityManager->getClassMetadata(VideoPlayerSession::class)->getTableName()} s
                INNER JOIN {$this->entityManager->getClassMetadata(Video::class)->getTableName()} v
                ON v.id = s.videos_id
                WHERE
                    v.id = :vid
                ;
            ";
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->bindValue(':vid', $video->getId());
        $resultSet = $stmt->executeQuery();

        $numberOfVideoPlayerSessions = 0;
        foreach ($resultSet->fetchAllAssociative() as $row) {
            $numberOfVideoPlayerSessions = $row['cnt'];
        }

        return $numberOfVideoPlayerSessions;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function getNumberOfStartedVideoPlayerSessions(
        Video $video
    ): int
    {
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
        $stmt->bindValue(':vid', $video->getId());
        $resultSet = $stmt->executeQuery();

        $numberOfStartedVideoPlayerSessions = 0;
        foreach ($resultSet->fetchAllAssociative() as $row) {
            $numberOfStartedVideoPlayerSessions = $row['cnt'];
        }

        return $numberOfStartedVideoPlayerSessions;
    }
}
