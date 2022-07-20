<?php

namespace App\Service\Feature\Recordings;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Recordings\RecordingSession;
use App\Entity\Feature\Recordings\RecordingSessionVideoChunk;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;

class RecordingSessionService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /** @throws Exception */
    public function handleRecordingSessionVideoChunk(
        string $recordingSessionId,
        string $userId,
        string $chunkName,
        string $videoChunkFilePath,
        string $mimeType
    ): RecordingSessionVideoChunk {

        $recordingSession = $this->entityManager->find(RecordingSession::class, $recordingSessionId);
        if (is_null($recordingSession)) {
            throw new InvalidArgumentException("No recording session with id '$recordingSessionId'.");
        }

        $user = $this->entityManager->find(User::class, $userId);
        if (is_null($user)) {
            throw new InvalidArgumentException("No user with id '$recordingSessionId'.");
        }

        if ($user->getId() !== $recordingSession->getUser()->getId()) {
            throw new Exception("User id '{$user->getId()}' does not match the user id of session '$recordingSessionId'.");
        }

        $chunk = new RecordingSessionVideoChunk();
        $chunk->setRecordingSession($recordingSession);
        $chunk->setName($chunkName);
        $chunk->setMimeType($mimeType);

        $this->entityManager->persist($chunk);
        $this->entityManager->flush();
    }
}
