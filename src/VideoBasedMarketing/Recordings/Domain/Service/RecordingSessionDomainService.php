<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Domain\Message\RecordingSessionCreatedEventMessage;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Messenger\MessageBusInterface;


class RecordingSessionDomainService
{
    private EntityManagerInterface $entityManager;

    private MessageBusInterface $messageBus;

    private VideoDomainService $videoDomainService;

    private RecordingsInfrastructureService $recordingsInfrastructureService;

    public function __construct(
        EntityManagerInterface            $entityManager,
        MessageBusInterface               $messageBus,
        VideoDomainService                $videoDomainService,
        RecordingsInfrastructureService   $recordingsInfrastructureService
    )
    {
        $this->entityManager = $entityManager;
        $this->messageBus = $messageBus;
        $this->videoDomainService = $videoDomainService;
        $this->recordingsInfrastructureService = $recordingsInfrastructureService;
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
}
