<?php

namespace App\Service\Feature\Recordings;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Recordings\RecordingSession;
use App\Entity\Feature\Recordings\RecordingSessionVideoChunk;
use App\Service\Aspect\Filesystem\FilesystemService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;

class RecordingSessionService
{
    private EntityManagerInterface $entityManager;

    private FilesystemService $filesystemService;

    public function __construct(
        EntityManagerInterface $entityManager,
        FilesystemService $filesystemService
    ) {
        $this->entityManager = $entityManager;
        $this->filesystemService = $filesystemService;
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

        $fs = new Filesystem();

        $fs->copy(
            $videoChunkFilePath,
            $this->filesystemService->getPublicWebfolderGeneratedContentPath([
                'recording-sessions',
                $recordingSession->getId(),
                'video-chunks',
                $chunk->getId()
            ])
        );

        $fs->rename(
            $videoChunkFilePath,
            $this->filesystemService->getContentStoragePath([
                'recording-sessions',
                $recordingSession->getId(),
                'video-chunks',
                $chunk->getId()
            ])
        );

        $this->entityManager->flush();

        return $chunk;
    }
}
