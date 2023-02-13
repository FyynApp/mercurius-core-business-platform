<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Domain\Event\RecordingSessionWillBeRemovedEvent;
use App\VideoBasedMarketing\Recordings\Domain\Message\RecordingSessionCreatedEventMessage;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use ValueError;


readonly class RecordingSessionDomainService
{
    public function __construct(
        private EntityManagerInterface          $entityManager,
        private MessageBusInterface             $messageBus,
        private VideoDomainService              $videoDomainService,
        private RecordingsInfrastructureService $recordingsInfrastructureService,
        private EventDispatcherInterface        $eventDispatcher
    )
    {
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
            ->recordingsInfrastructureService
            ->checkAndHandleVideoAssetGenerationForUser(
                $recordingSession->getUser(),
                false,
                true
            );

        return $video;
    }

    /**
     * @throws Exception
     */
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

    public function removeRecordingSession(
        RecordingSession $recordingSession
    ): void
    {
        if (!is_null($recordingSession->getVideo())) {
            throw new ValueError("Recording session '{$recordingSession->getId()}' is already linked to a video and cannot be removed anymore.");
        }

        $this->eventDispatcher->dispatch(
            new RecordingSessionWillBeRemovedEvent($recordingSession),
            RecordingSessionWillBeRemovedEvent::class
        );

        $this->entityManager->remove($recordingSession);
        $this->entityManager->flush();
    }

    public function getMaxRecordingTime(
        User $user
    ): int
    {
        if ($user->isAdmin()) {
            return 3600;
        } else {
            return 300;
        }
    }
}
