<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Domain\Message\RecordingSessionCreatedEventMessage;
use App\VideoBasedMarketing\Recordings\Domain\Message\RecordingSessionRemovedEventMessage;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Messenger\MessageBusInterface;
use ValueError;


class RecordingSessionDomainService
{
    private EntityManagerInterface $entityManager;

    private MessageBusInterface $messageBus;

    private VideoDomainService $videoDomainService;

    private VideoAssetGenerationDomainService $videoAssetGenerationDomainService;

    public function __construct(
        EntityManagerInterface            $entityManager,
        MessageBusInterface               $messageBus,
        VideoDomainService                $videoDomainService,
        VideoAssetGenerationDomainService $videoAssetGenerationDomainService
    )
    {
        $this->entityManager = $entityManager;
        $this->messageBus = $messageBus;
        $this->videoDomainService = $videoDomainService;
        $this->videoAssetGenerationDomainService = $videoAssetGenerationDomainService;
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

        $this->messageBus->dispatch(
            new RecordingSessionCreatedEventMessage(
                $recordingSession
            )
        );

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

        $this->entityManager->remove($recordingSession);
        $this->entityManager->flush();
        $this->messageBus->dispatch(
            new RecordingSessionRemovedEventMessage(
                $recordingSession
            )
        );
    }
}
