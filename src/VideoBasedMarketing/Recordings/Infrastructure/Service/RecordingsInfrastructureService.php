<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\Service;

use App\Shared\Infrastructure\Message\ClearTusCacheCommandMessage;
use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\Shared\Infrastructure\Service\FilesystemService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Infrastructure\Entity\RecordingSessionVideoChunk;
use App\VideoBasedMarketing\Recordings\Infrastructure\Entity\VideoUpload;
use App\VideoBasedMarketing\Recordings\Infrastructure\Enum\AssetMimeType;
use App\VideoBasedMarketing\Recordings\Infrastructure\Message\GenerateMissingVideoAssetsCommandMessage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use TusPhp\Events\UploadComplete;
use TusPhp\Tus\Server;


class RecordingsInfrastructureService
{
    private const VIDEO_ASSETS_SUBFOLDER_NAME = 'video-assets';
    private const UPLOADED_VIDEO_ASSETS_SUBFOLDER_NAME = 'recordings-uploaded-video-assets';

    private EntityManagerInterface $entityManager;

    private FilesystemService $filesystemService;

    private LoggerInterface $logger;

    private RouterInterface $router;

    private CapabilitiesService $capabilitiesService;

    private MessageBusInterface $messageBus;

    private Server $tusServer;


    public function __construct(
        EntityManagerInterface $entityManager,
        FilesystemService      $filesystemService,
        LoggerInterface        $logger,
        RouterInterface        $router,
        CapabilitiesService    $capabilitiesService,
        MessageBusInterface    $messageBus,
        Server                 $tusServer
    )
    {
        $this->entityManager = $entityManager;
        $this->filesystemService = $filesystemService;
        $this->logger = $logger;
        $this->router = $router;
        $this->capabilitiesService = $capabilitiesService;
        $this->messageBus = $messageBus;
        $this->tusServer = $tusServer;
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
        $chunk->setCreatedAt(DateAndTimeService::getDateTime());
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

        $process = new Process(
            [
                'ffmpeg',

                '-ss',
                '1',

                '-t',
                '3',

                '-i',
                $this->getVideoChunkContentStorageFilePath(
                    $recordingSession->getRecordingSessionVideoChunks()->first()
                ),

                '-vf',
                'scale=520:-1',

                '-r',
                '7',

                '-q:v',
                '80',

                '-loop',
                '0',

                '-y',
                $this->getRecordingPreviewVideoPosterFilePath($recordingSession)
            ]
        );
        $process->run();

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

    public function getVideoPosterStillWithPlayOverlayForEmailAssetUrl(Video $video, bool $absoluteUrl = false): string
    {
        if ($video->hasAssetPosterStillWithPlayOverlayForEmailPng()) {
            return $this->router->generate(
                'videobasedmarketing.recordings.presentation.video.poster_still_with_play_overlay_for_email.asset',
                ['videoId' => $video->getId(), 'extension' => $this->mimeTypeToFileSuffix(AssetMimeType::ImagePng)],
                $absoluteUrl ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH
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
                [
                    'videoId' => $video->getId(),
                    'extension' => $this->mimeTypeToFileSuffix(AssetMimeType::VideoMp4),
                    'filename' => "fyyn.io-recording-{$video->getId()}.{$this->mimeTypeToFileSuffix(AssetMimeType::VideoMp4)}"
                ]
            );
        } elseif ($video->hasAssetFullWebm()) {
            return $this->router->generate(
                'videobasedmarketing.recordings.presentation.video.full.asset',
                [
                    'videoId' => $video->getId(),
                    'extension' => $this->mimeTypeToFileSuffix(AssetMimeType::VideoWebm),
                    'filename' => "fyyn.io-recording-{$video->getId()}.{$this->mimeTypeToFileSuffix(AssetMimeType::VideoWebm)}"
                ]
            );
        } else {
            return $this
                ->router
                ->generate(
                    'videobasedmarketing.recordings.presentation.video.missing_full_asset_placeholder'
                );
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
            AssetMimeType::ImagePng => 'png',
        };
    }


    /** @throws Exception */
    public function generateMissingVideoAssets(Video $video): void
    {
        if (   is_null($video->getRecordingSession())
            && is_null($video->getVideoUpload())
        ) {
            throw new Exception('Need video linked to either recording session or tus upload.');
        }

        if (   !is_null($video->getRecordingSession())
            && !is_null($video->getVideoUpload())
        ) {
            throw new Exception('Need video linked to only one of recording session or tus upload.');
        }

        if (!is_null($video->getVideoUpload())) {
            $this->tusServer->getCache()->setPrefix($video->getUser()->getId());
            $this->tusServer->getCache()->delete($video->getVideoUpload()->getTusToken());
        }

        $this->createFilesystemStructureForVideoAssets($video);

        if (!$video->hasAssetPosterStillWebp()) {
            $this->generateVideoAssetPosterStillWebp($video);
        }

        if (!$video->hasAssetPosterAnimatedWebp()) {
            $this->generateVideoAssetPosterAnimatedWebp($video);
        }

        if (!$video->hasAssetFullMp4()) {
            $this->generateVideoAssetFullMp4($video);
        }

        if (!$video->hasAssetPosterAnimatedGif()) {
            $this->generateVideoAssetPosterAnimatedGif($video);
        }

        if (!$video->hasAssetPosterStillWithPlayOverlayForEmailPng()) {
            $this->generateVideoAssetPosterStillWithPlayOverlayForEmailPng($video);
        }
    }

    public function generateVideoAssetPosterStillWebp(Video $video): void
    {
        $this->createFilesystemStructureForVideoAssets($video);

        if (!is_null($video->getRecordingSession())) {
            $sourcePath = $this->getVideoChunkContentStorageFilePath(
                $video->getRecordingSession()->getRecordingSessionVideoChunks()->first()
            );
        } else {
            $sourcePath = $this
                ->getContentStoragePathForVideoUpload($video->getVideoUpload());
        }

        for ($i = 50; $i > 0; $i--) {
            $process = new Process(
                [
                    'ffmpeg',

                    '-i',
                    $sourcePath,

                    '-vf',
                    "select=eq(n\,$i)",

                    '-q:v',
                    '70',

                    '-y',
                    $this->getVideoPosterStillAssetFilePath(
                        $video,
                        AssetMimeType::ImageWebp
                    )
                ]
            );
            $process->run();

            clearstatcache();

            if (!file_exists(
                $this->getVideoPosterStillAssetFilePath(
                    $video,
                    AssetMimeType::ImageWebp))
            ) {
                $this
                    ->logger
                    ->info(
                        "Failed to generate video asset 'poster still webp' for video {$video->getId()} from recording session {$video->getRecordingSession()->getId()} using commandline \"{$process->getCommandLine()}\". Command error output was '{$process->getErrorOutput()}'."
                    );
                continue;
            }

            $filesize = filesize($this->getVideoPosterStillAssetFilePath(
                $video,
                AssetMimeType::ImageWebp
            ));

            if ($filesize > 0) {
                $video->setHasAssetPosterStillWebp(true);

                $video->setAssetPosterStillWebpWidth(
                    $this->probeForVideoAssetWidth(
                        $this->getVideoPosterStillAssetFilePath($video, AssetMimeType::ImageWebp)
                    )
                );

                $video->setAssetPosterStillWebpHeight(
                    $this->probeForVideoAssetHeight(
                        $this->getVideoPosterStillAssetFilePath($video, AssetMimeType::ImageWebp)
                    )
                );

                $this->entityManager->persist($video);
                $this->entityManager->flush();
                break;
            }
        }
    }

    public function generateVideoAssetPosterStillWithPlayOverlayForEmailPng(Video $video): void
    {
        $this->createFilesystemStructureForVideoAssets($video);

        if (!file_exists(
            $this->getVideoPosterStillAssetFilePath(
                $video,
                AssetMimeType::ImageWebp
            )
        )) {
            $this->generateVideoAssetPosterStillWebp($video);
        }

        $posterStillWidth = $video->getAssetPosterStillWebpWidth();
        $posterStillHeight = $video->getAssetPosterStillWebpHeight();

        // on macOS, ffmpeg command for generateVideoAssetPosterStillWebp fails for unknown reasons
        if (   !$posterStillWidth > 0
            || !$posterStillHeight > 0
        ) {
            return;
        }

        $dstImage = imagecreatetruecolor(
            $posterStillWidth,
            $posterStillHeight
        );

        $srcImagePoster = imagecreatefromwebp(
            $this->getVideoPosterStillAssetFilePath(
                $video,
                AssetMimeType::ImageWebp
            )
        );

        $srcImageOverlay = imagecreatefromwebp(
            __DIR__ . '/../../../../../public/assets/images/videobasedmarketing/mailings/play-button-overlay.webp'
        );

        imagecopy(
            $dstImage,
            $srcImagePoster,
            0,
            0,
            0,
            0,
            $posterStillWidth,
            $posterStillHeight
        );

        $dstY = $posterStillHeight / 2 / 2;
        $dstY = (int)round($dstY, 0, PHP_ROUND_HALF_DOWN);
        $dstHeight = $posterStillHeight / 2 * 1.5 - $dstY;

        $dstWidth = $dstHeight;
        $dstX = $posterStillWidth / 2 - $dstWidth / 2;
        $dstX = (int)round($dstX, 0, PHP_ROUND_HALF_DOWN);


        imagecopyresized(
            $dstImage,
            $srcImageOverlay,
            $dstX,
            $dstY,
            0,
            0,
            $dstWidth,
            $dstHeight,
            352,
            352
        );

        imagepng(
            $dstImage,
            $this->getVideoPosterStillWithPlayOverlayForEmailAssetFilePath(
                $video,
                AssetMimeType::ImagePng
            )
        );

        $video->setHasAssetPosterStillWithPlayOverlayForEmailPng(true);

        $video->setAssetPosterStillWithPlayOverlayForEmailPngWidth($video->getAssetPosterStillWebpWidth());

        $video->setAssetPosterStillWithPlayOverlayForEmailPngHeight($video->getAssetPosterStillWebpHeight());

        $this->entityManager->persist($video);
        $this->entityManager->flush();
    }

    public function generateVideoAssetPosterAnimatedWebp(Video $video): void
    {
        $this->createFilesystemStructureForVideoAssets($video);

        if (!is_null($video->getRecordingSession())) {
            $sourcePath = $this->getVideoChunkContentStorageFilePath(
                $video->getRecordingSession()->getRecordingSessionVideoChunks()->first()
            );
        } else {
            $sourcePath = $this
                ->getContentStoragePathForVideoUpload($video->getVideoUpload());
        }

        $process = new Process(
            [
                'ffmpeg',

                '-ss',
                '1',

                '-t',
                '3',

                '-i',
                $sourcePath,

                '-vf',
                'scale=520:-1',

                '-r',
                '7',

                '-q:v',
                '80',

                '-loop',
                '0',

                '-y',
                $this->getVideoPosterAnimatedAssetFilePath(
                    $video,
                    AssetMimeType::ImageWebp
                )
            ]
        );
        $process->run();

        $video->setHasAssetPosterAnimatedWebp(true);

        // See https://trac.ffmpeg.org/ticket/4907
        // ffmpeg/ffprobe can encode animated WebP files, but cannot decode them,
        // which is why we simply use the width and height of the still image WebP asset
        $video->setAssetPosterAnimatedWebpWidth($video->getAssetPosterStillWebpWidth());
        $video->setAssetPosterAnimatedWebpHeight($video->getAssetPosterStillWebpHeight());

        $this->entityManager->persist($video);
        $this->entityManager->flush();
    }

    private function generateVideoAssetPosterAnimatedGif(Video $video): void
    {
        $this->createFilesystemStructureForVideoAssets($video);

        if (!is_null($video->getRecordingSession())) {
            $sourcePath = $this->getVideoChunkContentStorageFilePath(
                $video->getRecordingSession()->getRecordingSessionVideoChunks()->first()
            );
        } else {
            $sourcePath = $this
                ->getContentStoragePathForVideoUpload($video->getVideoUpload());
        }

        $process = new Process(
            [
                'ffmpeg',

                '-ss',
                '1',

                '-t',
                '3',

                '-i',
                $sourcePath,

                '-vf',
                'fps=7,scale=480:-1:flags=lanczos,split[s0][s1];[s0]palettegen=max_colors=256:reserve_transparent=0[p];[s1][p]paletteuse=dither=none',

                '-r',
                '7',

                '-q:v',
                '20',

                '-loop',
                '0',

                '-y',
                $this->getVideoPosterAnimatedAssetFilePath(
                    $video,
                    AssetMimeType::ImageGif
                )
            ]
        );
        $process->run();

        $video->setHasAssetPosterAnimatedGif(true);
        $this->entityManager->persist($video);
        $this->entityManager->flush();
    }


    /**
     * @throws Exception
     */
    private function generateVideoAssetFullWebm(Video $video): void
    {
        if (!is_null($video->getVideoUpload())) {
            return;
        }

        $this->createFilesystemStructureForVideoAssets($video);

        $this->concatenateChunksIntoFile(
            $video->getRecordingSession(),
            $this->getVideoFullAssetFilePath($video, AssetMimeType::VideoWebm)
        );

        $video->setHasAssetFullWebm(true);

        $video->setAssetFullWebmFps(
            $this->probeForVideoAssetFps(
                $this->getVideoFullAssetFilePath($video, AssetMimeType::VideoWebm)
            )
        );

        $video->setAssetFullWebmSeconds(
            $this->probeForVideoAssetSeconds(
                $this->getVideoFullAssetFilePath($video, AssetMimeType::VideoWebm)
            )
        );

        $video->setAssetFullWebmWidth(
            $this->probeForVideoAssetWidth(
                $this->getVideoFullAssetFilePath($video, AssetMimeType::VideoWebm)
            )
        );

        $video->setAssetFullWebmHeight(
            $this->probeForVideoAssetHeight(
                $this->getVideoFullAssetFilePath($video, AssetMimeType::VideoWebm)
            )
        );

        $this->entityManager->persist($video);
        $this->entityManager->flush();
    }

    /**
     * @throws Exception
     */
    private function generateVideoAssetFullMp4(
        Video $video
    ): void
    {
        if (!is_null($video->getRecordingSession())) {
            if (!$video->hasAssetFullWebm()) {
                $this->generateVideoAssetFullWebm($video);
            }

            $sourcePath = $this->getVideoFullAssetFilePath(
                $video,
                AssetMimeType::VideoWebm
            );
        } else {
            $sourcePath = $this
                ->getContentStoragePathForVideoUpload($video->getVideoUpload());
        }

        $process = new Process(
            [
                'ffmpeg',

                '-i',
                $sourcePath,

                '-c:v',
                'libx264',

                '-profile:v',
                'main',

                '-level',
                '4.2',

                '-vf',
                'format=yuv420p,fps=60',

                '-c:a',
                'aac',

                '-movflags',
                '+faststart',

                '-y',
                $this->getVideoFullAssetFilePath(
                    $video,
                    AssetMimeType::VideoMp4
                )
            ]
        );
        $process->run();

        $video->setHasAssetFullMp4(true);

        $video->setAssetFullMp4Fps(
            $this->probeForVideoAssetFps(
                $this->getVideoFullAssetFilePath($video, AssetMimeType::VideoMp4)
            )
        );

        $video->setAssetFullMp4Seconds(
            $this->probeForVideoAssetSeconds(
                $this->getVideoFullAssetFilePath($video, AssetMimeType::VideoMp4)
            )
        );

        $video->setAssetFullMp4Width(
            $this->probeForVideoAssetWidth(
                $this->getVideoFullAssetFilePath($video, AssetMimeType::VideoMp4)
            )
        );

        $video->setAssetFullMp4Height(
            $this->probeForVideoAssetHeight(
                $this->getVideoFullAssetFilePath($video, AssetMimeType::VideoMp4)
            )
        );

        $this->entityManager->persist($video);
        $this->entityManager->flush();
    }


    /**
     * @throws Exception
     */
    private function probeForVideoAssetFps(
        string $filepath
    ): ?float
    {
        $process = new Process(
            [
                'ffprobe',

                '-v',
                'error',

                '-select_streams',
                'v',

                '-of',
                'default=noprint_wrappers=1:nokey=1',

                '-show_entries',
                'stream=r_frame_rate',

                $filepath
            ]
        );
        $process->run();

        $output = $process->getOutput();

        $outputParts = explode('/', $output);

        if (is_numeric($outputParts[0]) && is_numeric($outputParts[1])) {
            return $outputParts[0] / $outputParts[1];
        } else {
            $this
                ->logger
                ->info(
                    "Did not get numeric fps values for file at '$filepath' with command line \"{$process->getCommandLine()}\". Command error output was '{$process->getErrorOutput()}'."
                );
            return null;
        }
    }


    /**
     * @throws Exception
     */
    private function probeForVideoAssetSeconds(string $filepath): ?float
    {
        $process = new Process(
            [
                'ffprobe',

                '-v',
                'error',

                '-select_streams',
                'v',

                '-of',
                'default=noprint_wrappers=1:nokey=1',

                '-show_entries',
                'format=duration',

                $filepath
            ]
        );
        $process->run();

        $output = $process->getOutput();

        if (is_numeric($output)) {
            return (float)$output;
        } else {
            $this
                ->logger
                ->info(
                    "Did not get seconds value for file at '$filepath' with command line \"{$process->getCommandLine()}\". Command error output was '{$process->getErrorOutput()}'."
                );
            return null;
        }
    }


    /**
     * @throws Exception
     */
    private function probeForVideoAssetWidth(string $filepath): ?int
    {
        $process = new Process(
            [
                'ffprobe',

                '-v',
                'error',

                '-select_streams',
                'v',

                '-of',
                'default=noprint_wrappers=1:nokey=1',

                '-show_entries',
                'stream=width',

                '-of',
                'csv=p=0:s=x',

                $filepath
            ]
        );
        $process->run();

        $output = $process->getOutput();

        if (is_numeric($output)) {
            return (int)$output;
        } else {
            $this
                ->logger
                ->info(
                    "Did not get width for file at '$filepath' with command line \"{$process->getCommandLine()}\". Command error output was '{$process->getErrorOutput()}'."
                );
            return null;
        }
    }

    private function probeForVideoAssetHeight(string $filepath): ?int
    {
        $process = new Process(
            [
                'ffprobe',

                '-v',
                'error',

                '-select_streams',
                'v',

                '-of',
                'default=noprint_wrappers=1:nokey=1',

                '-show_entries',
                'stream=height',

                '-of',
                'csv=p=0:s=x',

                $filepath
            ]
        );
        $process->run();

        $output = $process->getOutput();

        if (is_numeric($output)) {
            return (int)$output;
        } else {
            $this
                ->logger
                ->info(
                    "Did not get height for file at '$filepath' with command line \"{$process->getCommandLine()}\". Command error output was '{$process->getErrorOutput()}'."
                );
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

    private function getVideoPosterStillWithPlayOverlayForEmailAssetFilePath(
        Video         $video,
        AssetMimeType $mimeType
    ): string
    {
        if ($mimeType !== AssetMimeType::ImagePng) {
            throw new InvalidArgumentException();
        }

        return $this->filesystemService->getPublicWebfolderGeneratedContentPath(
            [
                self::VIDEO_ASSETS_SUBFOLDER_NAME,
                $video->getId(),
                'poster-still-with-play-overlay-for-email.' . $this->mimeTypeToFileSuffix($mimeType)
            ]
        );
    }

    public function getVideoPosterStillWithPlayOverlayForEmailAssetRelativeFilePath(
        Video         $video,
        AssetMimeType $mimeType = AssetMimeType::ImagePng
    ): string
    {
        if ($mimeType !== AssetMimeType::ImagePng) {
            throw new InvalidArgumentException();
        }

        return self::VIDEO_ASSETS_SUBFOLDER_NAME . "/{$video->getId()}/poster-still-with-play-overlay-for-email.{$this->mimeTypeToFileSuffix($mimeType)}";
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

    /**
     * @throws \Doctrine\DBAL\Exception
     */
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

        $process = Process::fromShellCommandline(
            "cat $filenames > $targetFilePath"
        );

        $process->run();
    }

    public function checkAndHandleVideoAssetGenerationForUser(
        User $user,
        bool $basicAssetsAreRequiredImmediately = true
    ): void
    {
        $this
            ->logger
            ->debug("User '{$user->getId()}' has " . sizeof($user->getVideos()) . " videos.");

        foreach ($user->getVideos() as $video) {
            $this->checkAndHandleVideoAssetGenerationForVideo(
                $video,
                $basicAssetsAreRequiredImmediately
            );
        }
    }

    private function checkAndHandleVideoAssetGenerationForVideo(
        Video $video,
        bool $basicAssetsAreRequiredImmediately = true
    ): void
    {
        $this->logger->debug("Checking video '{$video->getId()}' for missing assets.");

        $forceGenerateMissingAssetsCommand = false;

        if (!$video->hasAssetPosterStillWebp()) {
            if ($basicAssetsAreRequiredImmediately) {
                $this->generateVideoAssetPosterStillWebp($video);
            } else {
                $forceGenerateMissingAssetsCommand = true;
            }
        }

        if (!$video->hasAssetPosterAnimatedWebp()) {
            if ($basicAssetsAreRequiredImmediately) {
                $this->generateVideoAssetPosterAnimatedWebp($video);
            } else {
                $forceGenerateMissingAssetsCommand = true;
            }
        }

        if (!$video->hasAssetPosterStillWithPlayOverlayForEmailPng()) {
            if ($basicAssetsAreRequiredImmediately) {
                $this->generateVideoAssetPosterStillWithPlayOverlayForEmailPng($video);
            } else {
                $forceGenerateMissingAssetsCommand = true;
            }
        }

        if (   !$video->hasAssetFullMp4()
            || !$video->hasAssetFullWebm()
            || $forceGenerateMissingAssetsCommand
        ) {
            if ($this->capabilitiesService->canHaveAllVideoAssetsGenerated($video->getUser())) {
                $this->messageBus->dispatch(
                    new GenerateMissingVideoAssetsCommandMessage($video)
                );
            }
        }
    }

    public function removeRecordingSessionAssets(
        RecordingSession $recordingSession
    ): void
    {
        $fs = new Filesystem();

        $fs->remove(
            $this->filesystemService->getContentStoragePath(
                [
                    'recording-sessions',
                    $recordingSession->getId()
                ]
            )
        );

        $fs->remove(
            $this->filesystemService->getPublicWebfolderGeneratedContentPath(
                [
                    'recording-sessions',
                    $recordingSession->getId()
                ]
            )
        );
    }


    /**
     * @throws Exception
     */
    public function handleCompletedVideoUpload(
        User           $user,
        string         $token,
        UploadComplete $event
    ): void
    {
        $fileMeta = $event->getFile()->details();

        $video = new Video(
            $user
        );

        $videoUpload = new VideoUpload(
            $video,
            $token,
            $fileMeta['metadata']['filename'],
            $fileMeta['metadata']['filetype']
        );

        $video->setTitle(
            implode(
                '.',
                array_slice(
                    explode(
                        '.',
                        $videoUpload->getFileName()
                    ),
                    0,
                    -1
                )
            )
        );

        $video->setVideoUpload($videoUpload);

        $this->entityManager->persist($videoUpload);
        $this->entityManager->persist($video);
        $this->entityManager->flush();

        $fs = new Filesystem();

        $fs->rename(
            $this->filesystemService->getContentStoragePath(
                [
                    self::UPLOADED_VIDEO_ASSETS_SUBFOLDER_NAME,
                    $user->getId(),
                    $videoUpload->getFileName()
                ]
            ),
            $this->filesystemService->getContentStoragePath(
                [
                    self::UPLOADED_VIDEO_ASSETS_SUBFOLDER_NAME,
                    $user->getId(),
                    "{$videoUpload->getId()}_{$videoUpload->getFileName()}"
                ]
            )
        );

        $this->messageBus->dispatch(
            new ClearTusCacheCommandMessage($user, $token)
        );

        $this->messageBus->dispatch(
            new GenerateMissingVideoAssetsCommandMessage($video)
        );
    }

    public function prepareVideoUpload(
        User   $user,
        Server $server
    ): void
    {
        $path = $this->filesystemService->getContentStoragePath(
            [
                self::UPLOADED_VIDEO_ASSETS_SUBFOLDER_NAME,
                $user->getId()
            ]
        );

        $fs = new Filesystem();
        $fs->mkdir($path);

        $server->setUploadDir($path);
    }

    private function getContentStoragePathForVideoUpload(
        VideoUpload $videoUpload
    ): string
    {
        return $this->filesystemService->getContentStoragePath(
            [
                self::UPLOADED_VIDEO_ASSETS_SUBFOLDER_NAME,
                $videoUpload->getVideo()->getUser()->getId(),
                "{$videoUpload->getId()}_{$videoUpload->getFileName()}"
            ]
        );
    }
}
