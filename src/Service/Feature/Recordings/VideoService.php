<?php

namespace App\Service\Feature\Recordings;

use App\Entity\Feature\Recordings\RecordingSession;
use App\Entity\Feature\Recordings\RecordingSessionVideoChunk;
use App\Entity\Feature\Recordings\Video;
use App\Message\Feature\Recordings\VideoCreatedMessage;
use App\Service\Aspect\Filesystem\FilesystemService;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\RouterInterface;

class VideoService
{
    private EntityManagerInterface $entityManager;

    private FilesystemService $filesystemService;

    private RecordingSessionService $recordingSessionService;

    private RouterInterface $router;

    private MessageBusInterface $messageBus;


    public function __construct(
        EntityManagerInterface $entityManager,
        FilesystemService $filesystemService,
        RecordingSessionService $recordingSessionService,
        RouterInterface $router,
        MessageBusInterface $messageBus
    ) {
        $this->entityManager = $entityManager;
        $this->filesystemService = $filesystemService;
        $this->recordingSessionService = $recordingSessionService;
        $this->router = $router;
        $this->messageBus = $messageBus;
    }


    public function getPosterStillAssetUrl(Video $video): string
    {
        if ($video->hasAssetPosterStillWebp()) {
            return $this->router->generate(
                'feature.recordings.video.poster_still.asset',
                ['videoId' => $video->getId(), 'extension' => Video::ASSET_MIME_TYPE_WEBP]
            );
        } else {
            return $this->router->generate('feature.recordings.video.missing_poster_asset_placeholder');
        }
    }


    /** @throws Exception */
    public function createVideoFromFinishedRecordingSession(RecordingSession $recordingSession): Video
    {
        if (!$recordingSession->isFinished()) {
            throw new InvalidArgumentException("Recording session '{$recordingSession->getId()} is not finished'.");
        }

        $video = new Video($recordingSession->getUser());
        $video->setRecordingSession($recordingSession);
        $recordingSession->setVideo($video);
        $recordingSession->setIsFinished(true);
        $this->entityManager->persist($video);
        $this->entityManager->persist($recordingSession);
        $this->entityManager->flush();

        if (is_null($recordingSession->getRecordingSessionVideoChunks()->first())) {
            throw new Exception("Cannot generate poster assets for video '{$video->getId()}' because its recording session '{$recordingSession->getId()}' does not have any video chunks.");
        }


        shell_exec("/usr/bin/env ffmpeg -i {$this->recordingSessionService->getVideoChunkContentStorageFilePath($recordingSession->getRecordingSessionVideoChunks()->first())} -vf \"select=eq(n\,50)\" -q:v 70 -y {$this->getPosterStillAssetFilePath($video, Video::ASSET_MIME_TYPE_WEBP)}");

        $video->setHasAssetPosterStillWebp(true);


        shell_exec("/usr/bin/env ffmpeg -ss 1 -t 3 -i {$this->recordingSessionService->getVideoChunkContentStorageFilePath($recordingSession->getRecordingSessionVideoChunks()->first())} -vf scale=520:-1 -r 7 -q:v 80 -loop 0 -y {$this->getPosterAnimatedAssetFilePath($video, Video::ASSET_MIME_TYPE_WEBP)}");

        $video->setHasAssetPosterAnimatedWebp(true);


        $this->entityManager->persist($video);
        $this->entityManager->flush();

        // Heavy-lifting stuff like missing video assets generation happens asynchronously
        $this->messageBus->dispatch(new VideoCreatedMessage($video));

        return $video;
    }


    /** @throws Exception */
    public function generateMissingAssets(
        Video $video
    ): void {

        if (is_null($video->getRecordingSession())) {
            throw new Exception('Need video linked to recording session.');
        }


        if (!$video->hasAssetFullWebm()) {
            $chunkFilesListPath = $this->filesystemService->getContentStoragePath([
                'recording-sessions',
                $video->getRecordingSession()->getId(),
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
            $resultSet = $stmt->executeQuery([':rsid' => $video->getRecordingSession()->getId()]);

            foreach ($resultSet->fetchAllAssociative() as $row) {
                $chunk = $this->entityManager->find(RecordingSessionVideoChunk::class, $row['id']);
                $chunkFilesListContent .= "file '{$this->recordingSessionService->getVideoChunkContentStorageFilePath($chunk)}'\n";
            }

            file_put_contents($chunkFilesListPath, $chunkFilesListContent);

            shell_exec("/usr/bin/env ffmpeg -f concat -safe 0 -i $chunkFilesListPath -c copy {$this->getFullAssetFilePath($video, Video::ASSET_MIME_TYPE_WEBM)}");

            $video->setHasAssetFullWebm(true);
            $this->entityManager->persist($video);
            $this->entityManager->flush();

            $fs = new Filesystem();
            $fs->remove($this->recordingSessionService->getVideoChunkContentStorageFolderPath($video->getRecordingSession()));
        }


        if (!$video->hasAssetPosterStillWebp()) {
            shell_exec("/usr/bin/env ffmpeg -i {$this->getFullAssetFilePath($video, Video::ASSET_MIME_TYPE_WEBM)} -vf \"select=eq(n\,50)\" -q:v 70 -y {$this->getPosterStillAssetFilePath($video, Video::ASSET_MIME_TYPE_WEBP)}");

            $video->setHasAssetPosterStillWebp(true);
            $this->entityManager->persist($video);
            $this->entityManager->flush();
        }


        if (!$video->hasAssetPosterAnimatedWebp()) {
            shell_exec("/usr/bin/env ffmpeg -ss 1 -t 3 -i {$this->getFullAssetFilePath($video, Video::ASSET_MIME_TYPE_WEBM)} -vf scale=520:-1 -r 7 -q:v 80 -loop 0 -y {$this->getPosterAnimatedAssetFilePath($video, Video::ASSET_MIME_TYPE_WEBP)}");

            $video->setHasAssetPosterAnimatedWebp(true);
            $this->entityManager->persist($video);
            $this->entityManager->flush();
        }


        if (!$video->hasAssetPosterAnimatedGif()) {
            // Poster animated gif
            shell_exec("/usr/bin/env ffmpeg -ss 1 -t 3 -i {$this->getFullAssetFilePath($video, Video::ASSET_MIME_TYPE_WEBM)} -vf \"fps=7,scale=480:-1:flags=lanczos,split[s0][s1];[s0]palettegen=max_colors=256:reserve_transparent=0[p];[s1][p]paletteuse=dither=none\" -r 7 -q:v 20 -loop 0 -y {$this->getPosterAnimatedAssetFilePath($video, Video::ASSET_MIME_TYPE_WEBP)}");

            $video->setHasAssetPosterAnimatedGif(true);
            $this->entityManager->persist($video);
            $this->entityManager->flush();
        }


        if (!$video->hasAssetFullMp4()) {
            shell_exec("/usr/bin/env ffmpeg -i {$this->getFullAssetFilePath($video, Video::ASSET_MIME_TYPE_WEBM)} -c:v libx264 -profile:v main -level 4.2 -vf format=yuv420p -c:a aac -movflags +faststart -y {$this->getFullAssetFilePath($video, Video::ASSET_MIME_TYPE_MP4)}");

            $video->setHasAssetFullMp4(true);
            $this->entityManager->persist($video);
            $this->entityManager->flush();
        }
    }



    /** @throws InvalidArgumentException */
    private function mimeTypeToFileSuffix(string $mimeType): string
    {
        return match ($mimeType) {
            Video::ASSET_MIME_TYPE_WEBP => 'webp',
            Video::ASSET_MIME_TYPE_GIF => 'gif',
            Video::ASSET_MIME_TYPE_WEBM => 'webm',
            Video::ASSET_MIME_TYPE_MP4 => 'mp4',
            default => throw new InvalidArgumentException("Unknown mime type '$mimeType'.")
        };
    }


    private function getPosterStillAssetFilePath(Video $video, string $mimeType): string
    {
        if ($mimeType !== Video::ASSET_MIME_TYPE_WEBP) {
            throw new InvalidArgumentException();
        }
        return $this->filesystemService->getPublicWebfolderGeneratedContentPath([
            'video-assets',
            $video->getId(),
            'poster-still.' . $this->mimeTypeToFileSuffix($mimeType)
        ]);
    }

    private function getPosterAnimatedAssetFilePath(Video $video, string $mimeType): string
    {
        if (   $mimeType !== Video::ASSET_MIME_TYPE_WEBP
            && $mimeType !== Video::ASSET_MIME_TYPE_GIF
        ) {
            throw new InvalidArgumentException();
        }
        return $this->filesystemService->getPublicWebfolderGeneratedContentPath([
            'video-assets',
            $video->getId(),
            'poster-still.' . $this->mimeTypeToFileSuffix($mimeType)
        ]);
    }

    private function getFullAssetFilePath(Video $video, string $mimeType): string
    {
        return $this->filesystemService->getPublicWebfolderGeneratedContentPath([
            'video-assets',
            $video->getId(),
            'full.' . $this->mimeTypeToFileSuffix($mimeType)
        ]);
    }
}
