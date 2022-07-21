<?php

namespace App\Service\Feature\Recordings;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Recordings\RecordingSession;
use App\Entity\Feature\Recordings\RecordingSessionFullVideo;
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
    ): RecordingSessionFullVideo
    {
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

        foreach ($recordingSession->getRecordingSessionVideoChunks() as $chunk) {
            $chunkFilesListContent .= "file '{$this->getVideoChunkContentStorageFilePath($chunk)}'\n";
        }

        file_put_contents($chunkFilesListPath, $chunkFilesListContent);

        shell_exec("/usr/bin/env ffmpeg -f concat -safe 0 -i $chunkFilesListPath -c copy {$this->getFullVideoVideoFilePath($recordingSession)}");


        $fs = new Filesystem();
        $fs->mkdir($this->getFullVideoPreviewPartsFolderPath($recordingSession));

        shell_exec("/usr/bin/env ffmpeg -i {$this->getFullVideoVideoFilePath($recordingSession)} -vf fps=1 -s 160x120 {$this->getFullVideoPreviewPartsFolderPath($recordingSession)}/frame%03d.jpg");

        shell_exec("/usr/bin/env ffmpeg -f image2 -framerate 1 -i {$this->getFullVideoPreviewPartsFolderPath($recordingSession)}/frame%03d.jpg -vf \"fps=1,scale=160:-1:flags=lanczos,split[s0][s1];[s0]palettegen=max_colors=256:reserve_transparent=0[p];[s1][p]paletteuse=dither=none\" {$this->getFullVideoPreviewFilePath($recordingSession)}");


        $fs->remove($this->getFullVideoPreviewPartsFolderPath($recordingSession));
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

    private function getFullVideoVideoFilePath(RecordingSession $recordingSession): string
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
            'full-video-preview.gif'
        ]);
    }

    private function getFullVideoPreviewPartsFolderPath(RecordingSession $recordingSession): string
    {
        return $this->filesystemService->getContentStoragePath([
            'recording-sessions',
            $recordingSession->getId(),
            'preview-parts'
        ]);
    }
}
