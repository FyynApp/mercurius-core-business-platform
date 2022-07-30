<?php

namespace App\Service\Feature\Recordings;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Recordings\RecordingSession;
use App\Entity\Feature\Recordings\RecordingSessionFullVideo;
use App\Entity\Feature\Recordings\RecordingSessionVideoChunk;
use App\Message\Feature\Recordings\RecordingSessionFinishedMessage;
use App\Service\Aspect\Filesystem\FilesystemService;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\MessageBusInterface;

class RecordingSessionService
{
    private EntityManagerInterface $entityManager;

    private FilesystemService $filesystemService;

    private LoggerInterface $logger;

    private MessageBusInterface $messageBus;

    public function __construct(
        EntityManagerInterface $entityManager,
        FilesystemService $filesystemService,
        LoggerInterface $logger,
        MessageBusInterface $messageBus
    ) {
        $this->entityManager = $entityManager;
        $this->filesystemService = $filesystemService;
        $this->logger = $logger;
        $this->messageBus = $messageBus;
    }


    public function handleRecordingSessionFinished(RecordingSession $recordingSession): void
    {
        $recordingSession->setIsFinished(true);
        $this->entityManager->persist($recordingSession);
        $this->entityManager->flush();

        // Heavy-lifting stuff like webm to mp4 conversion happens asynchronously
        $this->messageBus->dispatch(new RecordingSessionFinishedMessage($recordingSession));
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

        $fs->mkdir(
            $this->filesystemService->getPublicWebfolderGeneratedContentPath([
                'recording-sessions',
                $recordingSession->getId(),
                'video-chunks'
            ])
        );

        $fs->copy(
            $videoChunkFilePath,
            $this->filesystemService->getPublicWebfolderGeneratedContentPath([
                'recording-sessions',
                $recordingSession->getId(),
                'video-chunks',
                $chunk->getId()
            ])
        );


        $fs->mkdir($this->getVideoChunkContentStorageFolderPath($chunk->getRecordingSession()));

        $fs->rename(
            $videoChunkFilePath,
            $this->getVideoChunkContentStorageFilePath($chunk)
        );

        $this->entityManager->flush();

        return $chunk;
    }


    /** @throws Exception */
    public function generateFullVideo(
        string $recordingSessionId,
    ): RecordingSessionFullVideo {
        $recordingSession = $this->entityManager->find(RecordingSession::class, $recordingSessionId);

        if (is_null($recordingSession)) {
            throw new Exception("No recording session with id '$recordingSessionId'.");
        }

        $fullVideo = new RecordingSessionFullVideo();
        $fullVideo->setRecordingSession($recordingSession);
        $fullVideo->setMimeType($recordingSession->getRecordingSessionVideoChunks()->first()->getMimeType());

        $chunkFilesListPath = $this->filesystemService->getContentStoragePath([
            'recording-sessions',
            $recordingSession->getId(),
            'video-chunks-files.list'
        ]);
        $chunkFilesListContent = '';

        $sql = "
            SELECT id FROM {$this->entityManager->getClassMetadata(RecordingSessionVideoChunk::class)->getTableName()}
            WHERE recording_sessions_id = :rsid
            ORDER BY name " . Criteria::ASC . "
            ;
        ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $resultSet = $stmt->executeQuery([':rsid' => $recordingSession->getId()]);

        foreach ($resultSet->fetchAllAssociative() as $row) {
            $chunk = $this->entityManager->find(RecordingSessionVideoChunk::class, $row['id']);
            $chunkFilesListContent .= "file '{$this->getVideoChunkContentStorageFilePath($chunk)}'\n";
        }

        file_put_contents($chunkFilesListPath, $chunkFilesListContent);

        shell_exec("/usr/bin/env ffmpeg -f concat -safe 0 -i $chunkFilesListPath -c copy {$this->getFullVideoFilePath($recordingSession)}");

        // Video preview of full video
        shell_exec("/usr/bin/env ffmpeg -ss 1 -t 3 -i {$this->getFullVideoFilePath($recordingSession)} -vf scale=520:-1 -r 7 -q:v 80 -loop 0 -y {$this->getFullVideoPreviewFilePath($recordingSession)}");

        // Poster image preview of full video
        shell_exec("/usr/bin/env ffmpeg -i {$this->getFullVideoFilePath($recordingSession)} -vf \"select=eq(n\,50)\" -q:v 70 -y {$this->getFullVideoPosterFilePath($recordingSession)}");

        $fs = new Filesystem();
        $fs->remove($this->getVideoChunkContentStorageFolderPath($recordingSession));

        $this->entityManager->persist($fullVideo);
        $this->entityManager->flush();

        return $fullVideo;
    }

    private function getVideoChunkContentStorageFilePath(RecordingSessionVideoChunk $chunk): string
    {
        return $this->filesystemService->getContentStoragePath([
            'recording-sessions',
            $chunk->getRecordingSession()->getId(),
            'video-chunks',
            $chunk->getId() . '.webm'
        ]);
    }

    private function getVideoChunkContentStorageFolderPath(RecordingSession $recordingSession): string
    {
        return $this->filesystemService->getContentStoragePath([
            'recording-sessions',
            $recordingSession->getId(),
            'video-chunks'
        ]);
    }

    private function getFullVideoFilePath(RecordingSession $recordingSession): string
    {
        return $this->filesystemService->getPublicWebfolderGeneratedContentPath([
            'recording-sessions',
            $recordingSession->getId(),
            'full-video.webm'
        ]);
    }

    private function getFullVideoPreviewFilePath(RecordingSession $recordingSession): string
    {
        return $this->filesystemService->getPublicWebfolderGeneratedContentPath([
            'recording-sessions',
            $recordingSession->getId(),
            'full-video-preview.webp'
        ]);
    }

    private function getFullVideoPosterFilePath(RecordingSession $recordingSession): string
    {
        return $this->filesystemService->getPublicWebfolderGeneratedContentPath([
            'recording-sessions',
            $recordingSession->getId(),
            'full-video-poster.webp'
        ]);
    }
}
