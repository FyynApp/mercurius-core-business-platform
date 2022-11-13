<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Domain\Message\RecordingSessionCreatedEventMessage;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Messenger\MessageBusInterface;


class RecordingSessionDomainService
{
    private EntityManagerInterface $entityManager;

    private MessageBusInterface $messageBus;

    private VideoDomainService $videoDomainService;

    public function __construct(
        EntityManagerInterface $entityManager,
        MessageBusInterface    $messageBus,
        VideoDomainService     $videoDomainService
    )
    {
        $this->entityManager = $entityManager;
        $this->messageBus = $messageBus;
        $this->videoDomainService = $videoDomainService;
    }


    /**
     * @throws Exception
     */
    public function handleRecordingSessionFinished(
        RecordingSession $recordingSession
    ): Video
    {
        $recordingSession->setIsFinished(true);
        $this->entityManager->persist($recordingSession);
        $this->entityManager->flush();

        return $this->videoDomainService->createVideoEntityForFinishedRecordingSession(
            $recordingSession
        );
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
