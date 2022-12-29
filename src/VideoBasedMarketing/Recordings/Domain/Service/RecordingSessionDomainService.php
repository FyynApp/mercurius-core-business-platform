<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Domain\Event\RecordingSessionWillBeRemovedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use ValueError;


class RecordingSessionDomainService
{
    private EntityManagerInterface $entityManager;

    private VideoDomainService $videoDomainService;

    private VideoAssetGenerationDomainService $videoAssetGenerationDomainService;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        EntityManagerInterface            $entityManager,
        VideoDomainService                $videoDomainService,
        VideoAssetGenerationDomainService $videoAssetGenerationDomainService,
        EventDispatcherInterface          $eventDispatcher
    )
    {
        $this->entityManager = $entityManager;
        $this->videoDomainService = $videoDomainService;
        $this->videoAssetGenerationDomainService = $videoAssetGenerationDomainService;
        $this->eventDispatcher = $eventDispatcher;
    }


    /**
     * @throws Exception
     */
    public function handleRecordingSessionFinished(
        RecordingSession $recordingSession
    ): Video
    {
        if ($recordingSession->isFinished()) {
            return $recordingSession->getVideo();
        }

        if (is_null(
            $recordingSession
                ->getRecordingSessionVideoChunks()
                ->first()
        )) {
            throw new Exception(
                "Recording session '{$recordingSession->getId()}' does not have any video chunks."
            );
        }

        $recordingSession->setIsFinished(true);
        $this->entityManager->persist($recordingSession);
        $this->entityManager->flush();

        $video = $this
            ->videoDomainService
            ->createVideoEntityForFinishedRecordingSession($recordingSession);

        $this
            ->videoAssetGenerationDomainService
            ->checkAndHandleVideoAssetGeneration($recordingSession->getUser());

        return $video;
    }

    public function startRecordingSession(User $user): RecordingSession
    {
        $recordingSession = new RecordingSession($user);
        $this->entityManager->persist($recordingSession);
        $this->entityManager->flush($recordingSession);

        return $recordingSession;
    }

    /**
     * @throws ValueError
     */
    public function removeRecordingSession(
        RecordingSession $recordingSession
    ): void
    {
        if (!is_null($recordingSession->getVideo())) {
            throw new ValueError(
                "Recording session '{$recordingSession->getId()}' already has a video associated with it and therefore cannot be deleted."
            );
        }

        $this->eventDispatcher->dispatch(
            new RecordingSessionWillBeRemovedEvent($recordingSession)
        );

        $this->entityManager->remove($recordingSession);
        $this->entityManager->flush();
    }
}
