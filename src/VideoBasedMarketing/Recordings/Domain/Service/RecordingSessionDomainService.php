<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Presentationpages\Domain\Service\PresentationpagesService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Domain\Message\RecordingSessionCreatedEventMessage;
use App\VideoBasedMarketing\Recordings\Infrastructure\Message\GenerateMissingVideoAssetsCommandMessage;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\VideoInfrastructureService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


class RecordingSessionDomainService
{
    private EntityManagerInterface $entityManager;

    private MessageBusInterface $messageBus;

    public function __construct(
        EntityManagerInterface $entityManager,
        MessageBusInterface    $messageBus
    )
    {
        $this->entityManager = $entityManager;
        $this->messageBus = $messageBus;
    }


    /**
     * @throws Exception
     */
    public function handleRecordingSessionFinished(
        RecordingSession   $recordingSession,
        VideoDomainService $videoDomainService
    ): Video
    {
        $recordingSession->setIsFinished(true);
        $this->entityManager->persist($recordingSession);
        $this->entityManager->flush();

        return $videoDomainService->createVideoEntityForFinishedRecordingSession(
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
