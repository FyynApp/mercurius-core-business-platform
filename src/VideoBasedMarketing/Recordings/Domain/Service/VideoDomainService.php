<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Service;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\Shared\Infrastructure\Service\ShortIdService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Membership\Domain\Enum\MembershipPlanName;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipService;
use App\VideoBasedMarketing\Presentationpages\Domain\Service\PresentationpagesService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoFolder;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use InvalidArgumentException;


readonly class VideoDomainService
{
    public function __construct(
        private EntityManagerInterface    $entityManager,
        private PresentationpagesService  $presentationpagesService,
        private ShortIdService            $shortIdService,
        private MembershipService         $membershipService
    )
    {
    }


    /**
     * @return Video[]
     * @throws Exception
     */
    public function getAvailableVideosForCurrentlyActiveOrganization(
        User $user,
        VideoFolder|null|false $videoFolder = false
    ): array
    {
        /** @var ObjectRepository<Video> $repo */
        $repo = $this->entityManager->getRepository(Video::class);

        if ($videoFolder !== false) {

            if (!is_null($videoFolder)) {
                if ($user->getCurrentlyActiveOrganization()->getId() !== $videoFolder->getOrganization()->getId()) {
                    throw new Exception(
                        "User '{$user->getId()}' and video folder '{$videoFolder->getId()}' do not belong to the same organization."
                    );
                }
            }

            $allVideos = $repo->findBy(
                [
                    'organization' => $user->getCurrentlyActiveOrganization()->getId(),
                    'videoFolder' => $videoFolder
                ],
                ['createdAt' => Criteria::DESC]
            );
        } else {
            $allVideos = $repo->findBy(
                ['organization' => $user->getCurrentlyActiveOrganization()->getId()],
                ['createdAt' => Criteria::DESC]
            );
        }

        $videos = [];
        foreach ($allVideos as $video) {
            if (!$video->isDeleted()) {
                $videos[] = $video;
            }
        }

        return $videos;
    }


    /**
     * @throws Exception
     */
    public function getNewestUploadedVideoForUser(
        User $user
    ): ?Video
    {
        $sql = "
            SELECT v.id AS id

            FROM {$this->entityManager->getClassMetadata(Video::class)->getTableName()} v
            
            INNER JOIN {$this->entityManager->getClassMetadata(User::class)->getTableName()} u
            ON u.id = v.users_id
            
            WHERE
                u.id = :uid
                AND recordings_video_uploads_id IS NOT NULL
                
            ORDER BY created_at DESC
            LIMIT 1
            ;
        ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $resultSet = $stmt->executeQuery([':uid' => $user->getId()]);

        foreach ($resultSet->fetchAllAssociative() as $row) {
            return $this->entityManager->find(
                Video::class,
                $row['id']
            );
        }

        return null;
    }

    /** @throws Exception */
    public function createVideoEntityForFinishedRecordingSession(
        RecordingSession $recordingSession
    ): Video
    {
        if (!$recordingSession->isFinished()) {
            throw new InvalidArgumentException(
                "Recording session '{$recordingSession->getId()} is not finished'."
            );
        }

        $video = new Video($recordingSession->getUser());

        $video->setTitle(
            DateAndTimeService::getDateTime(
                'now',
                $recordingSession->getUser()->getUiTimezone()
            )
                ->format('Y-m-d H:i:s')
        );

        $video->getUser()->addVideo($video);

        $video->setRecordingSession($recordingSession);
        $recordingSession->setVideo($video);

        $this->entityManager->persist($video);
        $this->entityManager->persist($video->getUser());
        $this->entityManager->persist($recordingSession);

        $this->shortIdService->encodeObject($video);

        $this->entityManager->flush();

        $templates = $this
            ->presentationpagesService
            ->getVideoOnlyPresentationpageTemplatesForUser(
                $recordingSession->getUser()
            );

        $video->setVideoOnlyPresentationpageTemplate($templates[0]);

        return $video;
    }

    public function deleteVideo(Video $video): void
    {
        $video->setIsDeleted(true);
        $this->entityManager->persist($video);
        $this->entityManager->flush();
    }

    public function userIsOwnerOfVideo(
        ?User $user,
        Video $video
    ): bool
    {
        return !is_null($user) && $user->getId() === $video->getUser()->getId();
    }

    public function videoIsAvailableForDownload(Video $video): bool
    {
        return $video->hasAssetFullMp4();
    }

    public function videoCanBeShownOnPresentationpage(Video $video): bool
    {
        return !$video->isDeleted();
    }

    public function videoIsCurrentlyBeingProcessed(
        Video $video
    ): bool
    {
        if (!is_null($video->getVideoUpload())) {
            if (   !$video->hasAssetPosterStillWebp()
                || !$video->hasAssetPosterAnimatedWebp()
                || !$video->hasAssetFullMp4()
            ) {
                return true;
            }
        }

        return false;
    }

    public function prepareForShowingWithVideoOnlyPresentationpageTemplate(
        Video $video
    ): void
    {
        if (is_null($video->getVideoOnlyPresentationpageTemplate())) {
            $templates = $this
                ->presentationpagesService
                ->getVideoOnlyPresentationpageTemplatesForUser(
                    $video->getUser()
                );

            $video->setVideoOnlyPresentationpageTemplate($templates[0]);
            $this->entityManager->persist($video);
            $this->entityManager->flush();
        }
    }

    /**
     * @return array|Video[]
     */
    public function getNewestVideos(): array
    {
        /** @var ObjectRepository $repo */
        $repo = $this->entityManager->getRepository(Video::class);

        return $repo->findBy(
            [],
            ['createdAt' => Criteria::DESC]
        );
    }

    public function getMaxVideoUploadFilesize(
        User $user
    ): int
    {
        if (   $user->isAdmin()
            || $this->membershipService->getSubscribedMembershipPlanForCurrentlyActiveOrganization($user)->getName() === MembershipPlanName::Pro
        ) {
            return 2684354560; // 2.5 GiB
        }

        return 104857600; // 100 MiB
    }
}
