<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Service;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\Shared\Infrastructure\Service\ShortIdService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Presentationpages\Domain\Service\PresentationpagesService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;


readonly class VideoDomainService
{
    public function __construct(
        private EntityManagerInterface   $entityManager,
        private PresentationpagesService $presentationpagesService,
        private ShortIdService           $shortIdService,
    )
    {
    }


    /**
     * @return Video[]
     */
    public function getAvailableVideos(User $user): array
    {
        /** @var Video[] $allVideos */
        $allVideos = $user->getVideos()->toArray();

        $videos = [];
        foreach ($allVideos as $video) {
            if (!$video->isDeleted()) {
                $videos[] = $video;
            }
        }

        rsort($videos);

        return $videos;
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

    public function videoOnlyPresentationpageIsAvailable(Video $video): bool
    {
        return !$video->isDeleted();
    }

    public function videoIsCurrentlyBeingProcessed(
        Video $video
    ): bool
    {
        if (!is_null($video->getTusUpload())) {
            if (   !$video->hasAssetPosterStillWebp()
                || !$video->hasAssetPosterAnimatedWebp()
                || !$video->hasAssetFullMp4()
            ) {
                return true;
            }
        }

        return false;
    }
}
