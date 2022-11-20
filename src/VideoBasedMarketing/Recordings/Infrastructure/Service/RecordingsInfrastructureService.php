<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\Service;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\Shared\Infrastructure\Service\FilesystemService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Infrastructure\Entity\RecordingSessionVideoChunk;
use App\VideoBasedMarketing\Recordings\Infrastructure\Enum\AssetMimeType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\RouterInterface;


class RecordingsInfrastructureService
{
    private const VIDEO_ASSETS_SUBFOLDER_NAME = 'video-assets';

    private EntityManagerInterface $entityManager;

    private FilesystemService $filesystemService;

    private LoggerInterface $logger;

    private RouterInterface $router;

    public function __construct(
        EntityManagerInterface        $entityManager,
        FilesystemService             $filesystemService,
        LoggerInterface               $logger,
        RouterInterface               $router
    )
    {
        $this->entityManager = $entityManager;
        $this->filesystemService = $filesystemService;
        $this->logger = $logger;
        $this->router = $router;
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

        if ($user->getId() !== $recordingSession->getUser()->getId()) {
            throw new Exception(
                "User id '{$user->getId()}' does not match the user id of session '{$recordingSession->getId()}'."
            );
        }

        if ($chunkName === '1.webm') {
            if ($recordingSession->getRecordingSessionVideoChunks()->count() > 0) {

                $this->logger->info(
                    'Received 1.webm chunk while there already are existing chunks for this session - we assume this is a repeated recording, and remove all traces of the existing one.'
                );

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

        // When using the recorder, the final video chunk request is sent AFTER the 'recordingDone' request was sent.
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
    public function handleDoneChunkArrived(
        RecordingSession $recordingSession
    ): void
    {
        if ($recordingSession->getRecordingSessionVideoChunks()->count() < 1) {
            throw new Exception("Recording session '{$recordingSession->getId()}' needs at least one video chunk.");
        }

        shell_exec("/usr/bin/env ffmpeg -i {$this->getVideoChunkContentStorageFilePath($recordingSession->getRecordingSessionVideoChunks()->first())} -vf \"select=eq(n\,50)\" -q:v 70 -y {$this->getRecordingPreviewVideoPosterFilePath($recordingSession)}");

        $this->generateRecordingPreviewVideo($recordingSession);

        $recordingSession->setIsDone(true);
        $this->entityManager->persist($recordingSession);
        $this->entityManager->flush();
    }

    /**
     * @throws Exception
     */
    public function generateRecordingPreviewVideo(
        RecordingSession $recordingSession
    ): void
    {
        $this->concatenateChunksIntoFile(
            $recordingSession,
            $this->getRecordingPreviewVideoFilePath($recordingSession)
        );

        $recordingSession->setRecordingPreviewAssetHasBeenGenerated(true);
        $this->entityManager->persist($recordingSession);
        $this->entityManager->flush();
    }

    /**
     * @throws Exception
     */
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



    public function getVideoPosterStillAssetUrl(Video $video): string
    {
        if ($video->hasAssetPosterStillWebp()) {
            return $this->router->generate(
                'videobasedmarketing.recordings.presentation.video.poster_still.asset',
                ['videoId' => $video->getId(), 'extension' => $this->mimeTypeToFileSuffix(AssetMimeType::ImageWebp)]
            );
        } else {
            return $this->router->generate('videobasedmarketing.recordings.presentation.video.missing_poster_asset_placeholder');
        }
    }

    public function getVideoPosterAnimatedAssetUrl(Video $video): string
    {
        if ($video->hasAssetPosterAnimatedWebp()) {
            return $this->router->generate(
                'videobasedmarketing.recordings.presentation.video.poster_animated.asset',
                ['videoId' => $video->getId(), 'extension' => $this->mimeTypeToFileSuffix(AssetMimeType::ImageWebp)]
            );
        } else {
            return $this->router->generate('videobasedmarketing.recordings.presentation.video.missing_poster_asset_placeholder');
        }
    }

    public function getVideoFullAssetUrl(Video $video): string
    {
        if ($video->hasAssetFullMp4()) {
            return $this->router->generate(
                'videobasedmarketing.recordings.presentation.video.full.asset',
                ['videoId' => $video->getId(), 'extension' => $this->mimeTypeToFileSuffix(AssetMimeType::VideoMp4)]
            );
        } elseif ($video->hasAssetFullWebm()) {
            return $this->router->generate(
                'videobasedmarketing.recordings.presentation.video.full.asset',
                ['videoId' => $video->getId(), 'extension' => $this->mimeTypeToFileSuffix(AssetMimeType::VideoWebm)]
            );
        } else {
            return $this->router->generate('videobasedmarketing.recordings.presentation.video.missing_full_asset_placeholder');
        }
    }


    /** @throws InvalidArgumentException */
    public function mimeTypeToFileSuffix(AssetMimeType $mimeType): string
    {
        return match ($mimeType) {
            AssetMimeType::ImageWebp => 'webp',
            AssetMimeType::ImageGif => 'gif',
            AssetMimeType::VideoWebm => 'webm',
            AssetMimeType::VideoMp4 => 'mp4',
        };
    }


    /** @throws Exception */
    public function generateMissingVideoAssets(Video $video): void
    {
        if (is_null($video->getRecordingSession())) {
            throw new Exception('Need video linked to recording session.');
        }

        $this->createFilesystemStructureForVideoAssets($video);

        if (!$video->hasAssetPosterStillWebp()) {
            $this->generateVideoAssetPosterStillWebp($video);
        }

        if (!$video->hasAssetPosterAnimatedWebp()) {
            $this->generateVideoAssetPosterAnimatedWebp($video);
        }

        if (!$video->hasAssetFullMp4()) {
            $this->encodeVideoAssetFullMp4($video);
        }

        if (!$video->hasAssetPosterAnimatedGif()) {
            $this->generateVideoAssetPosterAnimatedGif($video);
        }
    }

    public function generateVideoAssetPosterStillWebp(Video $video): void
    {
        $this->createFilesystemStructureForVideoAssets($video);

        for ($i = 50; $i > 0; $i--) {
            shell_exec("/usr/bin/env ffmpeg -i {$this->getVideoChunkContentStorageFilePath($video->getRecordingSession()->getRecordingSessionVideoChunks()->first())} -vf \"select=eq(n\,$i)\" -q:v 70 -y {$this->getVideoPosterStillAssetFilePath($video, AssetMimeType::ImageWebp)}");

            clearstatcache();
            $filesize = filesize($this->getVideoPosterStillAssetFilePath($video, AssetMimeType::ImageWebp));

            if ($filesize > 0) {
                $video->setHasAssetPosterStillWebp(true);
                $this->entityManager->persist($video);
                $this->entityManager->flush();
                break;
            }
        }
    }

    public function generateVideoAssetPosterAnimatedWebp(Video $video): void
    {
        $this->createFilesystemStructureForVideoAssets($video);

        shell_exec("/usr/bin/env ffmpeg -ss 1 -t 3 -i {$this->getVideoChunkContentStorageFilePath($video->getRecordingSession()->getRecordingSessionVideoChunks()->first())} -vf scale=520:-1 -r 7 -q:v 80 -loop 0 -y {$this->getVideoPosterAnimatedAssetFilePath($video, AssetMimeType::ImageWebp)}");

        $video->setHasAssetPosterAnimatedWebp(true);
        $this->entityManager->persist($video);
        $this->entityManager->flush();
    }

    private function generateVideoAssetPosterAnimatedGif(Video $video): void
    {
        $this->createFilesystemStructureForVideoAssets($video);

        shell_exec("/usr/bin/env ffmpeg -ss 1 -t 3 -i {$this->getVideoChunkContentStorageFilePath($video->getRecordingSession()->getRecordingSessionVideoChunks()->first())} -vf \"fps=7,scale=480:-1:flags=lanczos,split[s0][s1];[s0]palettegen=max_colors=256:reserve_transparent=0[p];[s1][p]paletteuse=dither=none\" -r 7 -q:v 20 -loop 0 -y {$this->getVideoPosterAnimatedAssetFilePath($video, AssetMimeType::ImageGif)}");

        $video->setHasAssetPosterAnimatedGif(true);
        $this->entityManager->persist($video);
        $this->entityManager->flush();
    }


    /**
     * @throws Exception
     */
    private function generateVideoAssetFullWebm(Video $video): void
    {
        $this->createFilesystemStructureForVideoAssets($video);

        $this->concatenateChunksIntoFile(
            $video->getRecordingSession(),
            $this->getVideoFullAssetFilePath($video, AssetMimeType::VideoWebm)
        );

        $video->setHasAssetFullWebm(true);

        $video->setAssetFullWebmFps(
            $this->calculateVideoAssetFullFps(
                $this->getVideoFullAssetFilePath($video, AssetMimeType::VideoWebm)
            )
        );

        $video->setAssetFullWebmSeconds(
            $this->calculateVideoAssetFullSeconds(
                $this->getVideoFullAssetFilePath($video, AssetMimeType::VideoWebm)
            )
        );

        $this->entityManager->persist($video);
        $this->entityManager->flush();
    }

    /**
     * @throws Exception
     */
    private function encodeVideoAssetFullMp4(
        Video $video
    ): void
    {
        if (!$video->hasAssetFullWebm()) {
            $this->generateVideoAssetFullWebm($video);
        }

        // We generate the MP4 asset from the WebM asset that
        // was created by concatenating the WebM chunks.
        shell_exec(
            "/usr/bin/env ffmpeg \
            -i {$this->getVideoFullAssetFilePath($video, AssetMimeType::VideoWebm)} \
            -c:v libx264 \
            -profile:v main \
            -level 4.2 \
            -vf format=yuv420p,fps=60 \
            -c:a aac \
            -movflags \
            +faststart \
            -y {$this->getVideoFullAssetFilePath($video, AssetMimeType::VideoMp4)}
        ");

        $video->setHasAssetFullMp4(true);

        $video->setAssetFullMp4Fps(
            $this->calculateVideoAssetFullFps(
                $this->getVideoFullAssetFilePath($video, AssetMimeType::VideoMp4)
            )
        );

        $video->setAssetFullMp4Seconds(
            $this->calculateVideoAssetFullSeconds(
                $this->getVideoFullAssetFilePath($video, AssetMimeType::VideoMp4)
            )
        );

        $this->entityManager->persist($video);
        $this->entityManager->flush();
    }


    /**
     * @throws Exception
     */
    private function calculateVideoAssetFullFps(string $filepath): float
    {
        $command = "/usr/bin/env ffprobe -v error -select_streams v -of default=noprint_wrappers=1:nokey=1 -show_entries stream=r_frame_rate $filepath";
        $this->logger->debug("calculateVideoAssetFullFps command is '$command'.");
        $output = shell_exec($command);

        $outputParts = explode('/', $output);

        if (is_numeric($outputParts[0]) && is_numeric($outputParts[1])) {
            return $outputParts[0] / $outputParts[1];
        } else {
            throw new Exception("Did not get numeric fps values for file at $filepath.");
        }
    }


    /**
     * @throws Exception
     */
    private function calculateVideoAssetFullSeconds(string $filepath): ?float
    {
        $command = "/usr/bin/env ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 $filepath";

        $this->logger->debug("calculateVideoAssetFullSeconds command is '$command'.");

        $output = shell_exec($command);

        if (is_numeric($output)) {
            return (float)$output;
        } else {
            return null;
        }
    }


    private function createFilesystemStructureForVideoAssets(Video $video): void
    {
        $fs = new Filesystem();
        $fs->mkdir(
            $this->filesystemService->getPublicWebfolderGeneratedContentPath(
                [
                    self::VIDEO_ASSETS_SUBFOLDER_NAME,
                    $video->getId()
                ]
            )
        );
    }

    private function getVideoPosterStillAssetFilePath(
        Video         $video,
        AssetMimeType $mimeType
    ): string
    {
        if ($mimeType !== AssetMimeType::ImageWebp) {
            throw new InvalidArgumentException();
        }

        return $this->filesystemService->getPublicWebfolderGeneratedContentPath(
            [
                self::VIDEO_ASSETS_SUBFOLDER_NAME,
                $video->getId(),
                'poster-still.' . $this->mimeTypeToFileSuffix($mimeType)
            ]
        );
    }

    private function getVideoPosterAnimatedAssetFilePath(
        Video         $video,
        AssetMimeType $mimeType
    ): string
    {
        if ($mimeType !== AssetMimeType::ImageWebp
            && $mimeType !== AssetMimeType::ImageGif
        ) {
            throw new InvalidArgumentException();
        }

        return $this->filesystemService->getPublicWebfolderGeneratedContentPath(
            [
                self::VIDEO_ASSETS_SUBFOLDER_NAME,
                $video->getId(),
                'poster-animated.' . $this->mimeTypeToFileSuffix($mimeType)
            ]
        );
    }

    private function getVideoFullAssetFilePath(
        Video         $video,
        AssetMimeType $mimeType
    ): string
    {
        return $this->filesystemService->getPublicWebfolderGeneratedContentPath(
            [
                self::VIDEO_ASSETS_SUBFOLDER_NAME,
                $video->getId(),
                'full.' . $this->mimeTypeToFileSuffix($mimeType)
            ]
        );
    }

    private function concatenateChunksIntoFile(
        RecordingSession $recordingSession,
        string $targetFilePath
    ): void
    {
        $sql = "
                SELECT id
                FROM {$this->entityManager->getClassMetadata(RecordingSessionVideoChunk::class)->getTableName()}
                WHERE recording_sessions_id = :rsid
                ORDER BY created_at " . Criteria::ASC . "
                ;
            ";

        $stmt = $this
            ->entityManager
            ->getConnection()
            ->prepare($sql);

        $resultSet = $stmt->executeQuery([
            ':rsid' => $recordingSession->getId()
        ]);

        $filenames = [];
        foreach ($resultSet->fetchAllAssociative() as $row) {
            $chunk = $this->entityManager->find(RecordingSessionVideoChunk::class, $row['id']);
            $filenames[] = $this->getVideoChunkContentStorageFilePath($chunk);
        }
        $filenames = implode(' ', $filenames);

        $command = "/usr/bin/env cat $filenames > $targetFilePath";

        $this->logger->debug("generateVideoAssetFullWebm command is '$command'");

        shell_exec($command);
    }
}
