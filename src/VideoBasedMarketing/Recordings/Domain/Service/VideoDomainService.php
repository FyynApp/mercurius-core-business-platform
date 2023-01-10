<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Presentationpages\Domain\Service\PresentationpagesService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Symfony\Contracts\Translation\TranslatorInterface;


class VideoDomainService
{
    private EntityManagerInterface $entityManager;

    private TranslatorInterface $translator;

    private PresentationpagesService $presentationpagesService;

    public function __construct(
        EntityManagerInterface     $entityManager,
        TranslatorInterface        $translator,
        PresentationpagesService   $presentationpagesService,
    )
    {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->presentationpagesService = $presentationpagesService;
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
            $this->translator->trans(
                'new_video_title',
                ['{num}' => $recordingSession->getUser()->getVideos()->count() + 1],
                'videobasedmarketing.recordings'
            )
        );

        $video->getUser()->addVideo($video);

        $video->setRecordingSession($recordingSession);
        $recordingSession->setVideo($video);

        $this->entityManager->persist($video);
        $this->entityManager->persist($video->getUser());
        $this->entityManager->persist($recordingSession);

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
}
