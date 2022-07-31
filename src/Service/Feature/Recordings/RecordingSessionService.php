<?php

namespace App\Service\Feature\Recordings;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Recordings\RecordingSession;
use App\Entity\Feature\Recordings\RecordingSessionVideoChunk;
use App\Service\Aspect\Filesystem\FilesystemService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Filesystem\Filesystem;

class RecordingSessionService
{
    private EntityManagerInterface $entityManager;

    private FilesystemService $filesystemService;

    private VideoService $videoService;


    public function __construct(
        EntityManagerInterface $entityManager,
        FilesystemService $filesystemService,
        VideoService $videoService
    ) {
        $this->entityManager = $entityManager;
        $this->filesystemService = $filesystemService;
        $this->videoService = $videoService;
    }


    public function handleRecordingSessionFinished(RecordingSession $recordingSession): void
    {
        $recordingSession->setIsFinished(true);
        $this->entityManager->persist($recordingSession);
        $this->entityManager->flush();

        $this->videoService->createVideoFromFinishedRecordingSession($recordingSession);
    }

    /** @throws Exception */
    public function handleRecordingSessionVideoChunk(
        RecordingSession $recordingSession,
        User $user,
        string $chunkName,
        string $videoChunkFilePath,
        string $mimeType
    ): RecordingSessionVideoChunk {

        if ($user->getId() !== $recordingSession->getUser()->getId()) {
            throw new Exception("User id '{$user->getId()}' does not match the user id of session '{$recordingSession->getId()}'.");
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
    public function handleRecordingDone(RecordingSession $recordingSession): void
    {
        if ($recordingSession->getRecordingSessionVideoChunks()->count() < 1) {
            throw new Exception("Recording session '{$recordingSession->getId()}' needs at least one video chunk.");
        }

        shell_exec("/usr/bin/env ffmpeg -ss 1 -t 3 -i {$this->getVideoChunkContentStorageFilePath($recordingSession->getRecordingSessionVideoChunks()->first())} -vf scale=520:-1 -r 7 -q:v 80 -loop 0 -y {$this->getRecordingPreviewVideoFilePath($recordingSession)}");

        shell_exec("/usr/bin/env ffmpeg -i {$this->getVideoChunkContentStorageFilePath($recordingSession->getRecordingSessionVideoChunks()->first())} -vf \"select=eq(n\,50)\" -q:v 70 -y {$this->getRecordingPreviewVideoPosterFilePath($recordingSession)}");

        $recordingSession->setIsDone(true);
        $this->entityManager->persist($recordingSession);
        $this->entityManager->flush();
    }



    public function getVideoChunkContentStorageFilePath(RecordingSessionVideoChunk $chunk): string
    {
        return $this->filesystemService->getContentStoragePath([
            'recording-sessions',
            $chunk->getRecordingSession()->getId(),
            'video-chunks',
            $chunk->getId() . '.webm'
        ]);
    }

    public function getVideoChunkContentStorageFolderPath(RecordingSession $recordingSession): string
    {
        return $this->filesystemService->getContentStoragePath([
            'recording-sessions',
            $recordingSession->getId(),
            'video-chunks'
        ]);
    }


    private function getRecordingPreviewVideoFilePath(RecordingSession $recordingSession): string
    {
        return $this->filesystemService->getPublicWebfolderGeneratedContentPath([
            'recording-sessions',
            $recordingSession->getId(),
            'recording-preview-video.webm'
        ]);
    }

    private function getRecordingPreviewVideoPosterFilePath(RecordingSession $recordingSession): string
    {
        return $this->filesystemService->getPublicWebfolderGeneratedContentPath([
            'recording-sessions',
            $recordingSession->getId(),
            'recording-preview-video-poster.webp'
        ]);
    }


    private function getPosterUrl(): string
    {
        return '';
    }

    private function getVideoUrl(): string
    {
        return '';
    }
}
