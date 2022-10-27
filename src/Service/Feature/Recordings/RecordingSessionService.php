<?php

namespace App\Service\Feature\Recordings;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Recordings\RecordingSession;
use App\Entity\Feature\Recordings\RecordingSessionVideoChunk;
use App\Entity\Feature\Recordings\Video;
use App\Service\Aspect\DateAndTime\DateAndTimeService;
use App\Service\Aspect\Filesystem\FilesystemService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;


class RecordingSessionService
{
    private EntityManagerInterface $entityManager;

    private FilesystemService $filesystemService;

    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        FilesystemService      $filesystemService,
        LoggerInterface        $logger
    )
    {
        $this->entityManager = $entityManager;
        $this->filesystemService = $filesystemService;
        $this->logger = $logger;
    }


    public function createRecordingSession(User $user): RecordingSession
    {
        $recordingSession = new RecordingSession($user);
        $this->entityManager->persist($recordingSession);
        $this->entityManager->flush($recordingSession);

        return $recordingSession;
    }


    /**
     * @throws Exception
     */
    public function handleRecordingSessionFinished(
        RecordingSession $recordingSession,
        VideoService     $videoService
    ): Video
    {
        $recordingSession->setIsFinished(true);
        $this->entityManager->persist($recordingSession);
        $this->entityManager->flush();

        return $videoService->createVideoEntityForFinishedRecordingSession($recordingSession);
    }

    /** @throws Exception */
    public function handleRecordingSessionVideoChunk(
        RecordingSession $recordingSession,
        User             $user,
        string           $chunkName,
        string           $videoChunkFilePath,
        string           $mimeType
    ): RecordingSessionVideoChunk
    {

        if ($user->getId() !== $recordingSession->getUser()
                                                ->getId()) {
            throw new Exception("User id '{$user->getId()}' does not match the user id of session '{$recordingSession->getId()}'.");
        }

        if ($chunkName === '1.webm') {

            if ($recordingSession->getRecordingSessionVideoChunks()
                                 ->count() > 0) {

                $this->logger->info('Received 1.webm chunk while there already are existing chunks for this session - we assume this is a repeated recording, and remove all traces of the existing one.');

                foreach ($recordingSession->getRecordingSessionVideoChunks() as $existingChunk) {
                    $this->entityManager->remove($existingChunk);
                    $this->entityManager->flush();
                }
                $recordingSession->setRecordingSessionVideoChunks(new ArrayCollection());
                $recordingSession->setIsDone(false);
                $recordingSession->setRecordingPreviewAssetHasBeenGenerated(false);
                $this->entityManager->persist($recordingSession);
                $this->entityManager->flush();
            }
        }

        $chunk = new RecordingSessionVideoChunk();
        $chunk->setRecordingSession($recordingSession);
        $chunk->setName($chunkName);
        $chunk->setMimeType($mimeType);
        $chunk->setCreatedAt(DateAndTimeService::getDateTimeUtc());
        $this->entityManager->persist($chunk);

        $fs = new Filesystem();

        $fs->mkdir(
            $this->filesystemService->getPublicWebfolderGeneratedContentPath(
                [
                    'recording-sessions',
                    $recordingSession->getId(),
                    'video-chunks'
                ]
            )
        );

        $fs->copy(
            $videoChunkFilePath,
            $this->filesystemService->getPublicWebfolderGeneratedContentPath(
                [
                    'recording-sessions',
                    $recordingSession->getId(),
                    'video-chunks',
                    $chunk->getId()
                ]
            )
        );


        $fs->mkdir($this->getVideoChunkContentStorageFolderPath($chunk->getRecordingSession()));

        $fs->rename(
            $videoChunkFilePath,
            $this->getVideoChunkContentStorageFilePath($chunk)
        );

        $this->entityManager->flush();

        // The final video chunk request is sent AFTER the 'recordingDone' request was sent.
        // This is because the 'recordingDone' request is sent the moment the user hits 'Stop recording',
        // but in this moment a 5-second-recording-chunk is still in the making, and it's only sent
        // with the next request. We therefore need to treat the video chunk that is received after
        // the recordingDone request has been received in a special way: it's the request that allows
        // us to generate the recording preview asset.
        // Setting the recordingPreviewAssetHasBeenGenerated info to true on the entity then allows
        // the RecordingsController::recordingPreviewAssetRedirectAction, which waits for this info to
        // become true, to redirect to the generated asset.
        if ($recordingSession->isDone()) {
            $this->logger->info("Received a video chunk after the 'recordingDone' request has been received - starting full webm asset generation.");
            $this->generateRecordingPreviewVideo($recordingSession);
        }

        return $chunk;
    }

    /** @throws Exception */
    public function handleRecordingDone(RecordingSession $recordingSession): void
    {
        if ($recordingSession->getRecordingSessionVideoChunks()
                             ->count() < 1) {
            throw new Exception("Recording session '{$recordingSession->getId()}' needs at least one video chunk.");
        }

        shell_exec("/usr/bin/env ffmpeg -i {$this->getVideoChunkContentStorageFilePath($recordingSession->getRecordingSessionVideoChunks()->first())} -vf \"select=eq(n\,50)\" -q:v 70 -y {$this->getRecordingPreviewVideoPosterFilePath($recordingSession)}");

        $recordingSession->setIsDone(true);
        $this->entityManager->persist($recordingSession);
        $this->entityManager->flush();
    }

    public function generateRecordingPreviewVideo(RecordingSession $recordingSession): void
    {
        shell_exec("/usr/bin/env ffmpeg -f concat -safe 0 -i {$this->generateVideoChunksFilesListFile($recordingSession)} -c copy -y {$this->getRecordingPreviewVideoFilePath($recordingSession)}");

        $recordingSession->setRecordingPreviewAssetHasBeenGenerated(true);
        $this->entityManager->persist($recordingSession);
        $this->entityManager->flush();
    }

    public function generateVideoChunksFilesListFile(RecordingSession $recordingSession): string
    {
        $chunkFilesListPath = $this->filesystemService->getContentStoragePath(
            [
                'recording-sessions',
                $recordingSession->getId(),
                'video-chunks-files.' . bin2hex(random_bytes(8)) . '.list'
            ]
        );
        $chunkFilesListContent = '';

        $sql = "
                SELECT id FROM {$this->entityManager->getClassMetadata(RecordingSessionVideoChunk::class)->getTableName()}
                WHERE recording_sessions_id = :rsid
                ORDER BY created_at " . Criteria::ASC . "
                ;
            ";

        $stmt = $this->entityManager->getConnection()
                                    ->prepare($sql);
        $resultSet = $stmt->executeQuery([':rsid' => $recordingSession->getId()]);

        foreach ($resultSet->fetchAllAssociative() as $row) {
            $chunk = $this->entityManager->find(RecordingSessionVideoChunk::class, $row['id']);
            $chunkFilesListContent .= "file '{$this->getVideoChunkContentStorageFilePath($chunk)}'\n";
        }

        file_put_contents($chunkFilesListPath, $chunkFilesListContent);

        return $chunkFilesListPath;
    }

    public function getVideoChunkContentStorageFilePath(RecordingSessionVideoChunk $chunk): string
    {
        return $this->filesystemService->getContentStoragePath(
            [
                'recording-sessions',
                $chunk->getRecordingSession()
                      ->getId(),
                'video-chunks',
                $chunk->getId() . '.webm'
            ]
        );
    }

    public function getVideoChunkContentStorageFolderPath(RecordingSession $recordingSession): string
    {
        return $this->filesystemService->getContentStoragePath(
            [
                'recording-sessions',
                $recordingSession->getId(),
                'video-chunks'
            ]
        );
    }

    public function getRecordingPreviewVideoFilePath(RecordingSession $recordingSession): string
    {
        return $this->filesystemService->getPublicWebfolderGeneratedContentPath(
            [
                'recording-sessions',
                $recordingSession->getId(),
                'recording-preview-video.webm'
            ]
        );
    }

    private function getRecordingPreviewVideoPosterFilePath(RecordingSession $recordingSession): string
    {
        return $this->filesystemService->getPublicWebfolderGeneratedContentPath(
            [
                'recording-sessions',
                $recordingSession->getId(),
                'recording-preview-video-poster.webp'
            ]
        );
    }
}
