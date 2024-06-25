<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\Service;

use App\Shared\Infrastructure\SymfonyMessage\ClearTusCacheCommandSymfonyMessage;
use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\Shared\Infrastructure\Service\FilesystemService;
use App\Shared\Infrastructure\Service\ShortIdService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoFolder;
use App\VideoBasedMarketing\Recordings\Domain\Enum\VideoSourceType;
use App\VideoBasedMarketing\Recordings\Infrastructure\Entity\RecordingSessionVideoChunk;
use App\VideoBasedMarketing\Recordings\Infrastructure\Entity\VideoUpload;
use App\VideoBasedMarketing\Recordings\Infrastructure\Enum\AssetMimeType;
use App\VideoBasedMarketing\Recordings\Infrastructure\SymfonyMessage\GenerateMissingVideoAssetsCommandSymfonyMessage;
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
use ValueError;


readonly class RecordingsInfrastructureService
{
    private const VIDEO_ASSETS_SUBFOLDER_NAME = 'video-assets';
    private const UPLOADED_VIDEO_ASSETS_SUBFOLDER_NAME = 'recordings-uploaded-video-assets';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private FilesystemService      $filesystemService,
        private LoggerInterface        $logger,
        private RouterInterface        $router,
        private CapabilitiesService    $capabilitiesService,
        private MessageBusInterface    $messageBus,
        private Server                 $tusServer,
        private ShortIdService         $shortIdService
    )
    {
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
        $success = $this->concatenateChunksIntoFile(
            $recordingSession,
            $this->getRecordingPreviewVideoFilePath($recordingSession)
        );

        if ($success) {
            $recordingSession->setRecordingPreviewAssetHasBeenGenerated(true);
            $this->entityManager->persist($recordingSession);
            $this->entityManager->flush();
        }
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
        $stmt->bindValue(':rsid', $recordingSession->getId());
        $resultSet = $stmt->executeQuery();

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

    public function getRecordingPreviewVideoFilePath(
        RecordingSession $recordingSession
    ): string
    {
        $chunksMimeType = $this->getRecordingSessionChunksMimeType($recordingSession);

        if (is_null($chunksMimeType)) {
            throw new ValueError(
                "Could not detect chunks mime type of recording session '{$recordingSession->getId()}'."
            );
        }

        return $this->filesystemService->getPublicWebfolderGeneratedContentPath(
            [
                'recording-sessions',
                $recordingSession->getId(),
                'recording-preview-video.'
                . $this->mimeTypeToFileSuffix(
                    $chunksMimeType
                )
            ]
        );
    }

    public function getRecordingSessionChunksMimeType(
        RecordingSession $recordingSession
    ): ?AssetMimeType
    {
        /** @var null|RecordingSessionVideoChunk $chunk */
        $chunk = $recordingSession->getRecordingSessionVideoChunks()->first();

        if (is_null($chunk)) {
            return null;
        }

        $mimeType = $chunk->getMimeType();

        if (mb_strstr($mimeType, ';')) {
            $mimeType = explode(';', $mimeType)[0];
        }

        if (mb_strstr($mimeType, ',')) {
            $mimeType = explode(',', $mimeType)[0];
        }

        return AssetMimeType::tryFrom($mimeType);
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
                [
                    'videoId' => $video->getId(),
                    'extension' => $this->mimeTypeToFileSuffix(AssetMimeType::ImageWebp)
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
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

    /**
     * @throws Exception
     */
    public function getVideoFullAssetUrl(
        Video $video,
        AssetMimeType $preferredAssetMimeType = AssetMimeType::VideoMp4
    ): string
    {
        if ($preferredAssetMimeType === AssetMimeType::VideoMp4) {
            if ($video->hasAssetFullMp4()) {
                return $this->router->generate(
                    'videobasedmarketing.recordings.presentation.video.full.asset',
                    [
                        'videoId' => $video->getId(),
                        'extension' => $this->mimeTypeToFileSuffix(AssetMimeType::VideoMp4),
                        'filename' => "fyyn.io-recording-{$video->getId()}.{$this->mimeTypeToFileSuffix(AssetMimeType::VideoMp4)}"
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
            } elseif ($video->hasAssetFullWebm()) {
                return $this->router->generate(
                    'videobasedmarketing.recordings.presentation.video.full.asset',
                    [
                        'videoId' => $video->getId(),
                        'extension' => $this->mimeTypeToFileSuffix(AssetMimeType::VideoWebm),
                        'filename' => "fyyn.io-recording-{$video->getId()}.{$this->mimeTypeToFileSuffix(AssetMimeType::VideoWebm)}"
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
            } else {
                return $this
                    ->router
                    ->generate(
                        'videobasedmarketing.recordings.presentation.video.missing_full_asset_placeholder',
                        [],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );
            }

        } elseif ($preferredAssetMimeType === AssetMimeType::VideoWebm) {
            if ($video->hasAssetFullWebm()) {
                return $this->router->generate(
                    'videobasedmarketing.recordings.presentation.video.full.asset',
                    [
                        'videoId' => $video->getId(),
                        'extension' => $this->mimeTypeToFileSuffix(AssetMimeType::VideoWebm),
                        'filename' => "fyyn.io-recording-{$video->getId()}.{$this->mimeTypeToFileSuffix(AssetMimeType::VideoWebm)}"
                    ]
                );
            }
            elseif ($video->hasAssetFullMp4()) {
                return $this->router->generate(
                    'videobasedmarketing.recordings.presentation.video.full.asset',
                    [
                        'videoId' => $video->getId(),
                        'extension' => $this->mimeTypeToFileSuffix(AssetMimeType::VideoMp4),
                        'filename' => "fyyn.io-recording-{$video->getId()}.{$this->mimeTypeToFileSuffix(AssetMimeType::VideoMp4)}"
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

        throw new Exception("Asset mime type '{$preferredAssetMimeType->value}' is not supported.");
    }

    public function getVideoFullAssetMimeType(
        Video $video,
        AssetMimeType $preferredAssetMimeType = AssetMimeType::VideoMp4
    ): ?AssetMimeType
    {
        if ($preferredAssetMimeType === AssetMimeType::VideoMp4) {
            if ($video->hasAssetFullMp4()) {
                return AssetMimeType::VideoMp4;
            } elseif ($video->hasAssetFullWebm()) {
                return AssetMimeType::VideoWebm;
            } else {
                return null;
            }
        } else {
            if ($video->hasAssetFullWebm()) {
                return AssetMimeType::VideoWebm;
            } elseif ($video->hasAssetFullMp4()) {
                return AssetMimeType::VideoMp4;
            } else {
                return null;
            }
        }
    }

    public function getVideoForAnalyticsWidgetAssetUrl(Video $video, bool $absoluteUrl = false): string
    {
        if ($video->hasAssetForAnalyticsWidgetMp4()) {
            return $this->router->generate(
                'videobasedmarketing.recordings.presentation.video.for_analytics_widget.asset',
                ['videoId' => $video->getId(), 'extension' => $this->mimeTypeToFileSuffix(AssetMimeType::VideoMp4)],
                $absoluteUrl ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH
            );
        } else {
            return $this->router->generate('videobasedmarketing.recordings.presentation.video.missing_full_asset_placeholder');
        }
    }
    

    /** @throws InvalidArgumentException */
    public static function mimeTypeToFileSuffix(
        AssetMimeType $mimeType
    ): string
    {
        return match ($mimeType) {
            AssetMimeType::ImageWebp => 'webp',
            AssetMimeType::ImageGif  => 'gif',
            AssetMimeType::VideoWebm => 'webm',
            AssetMimeType::VideoMp4  => 'mp4',
            AssetMimeType::ImagePng  => 'png',
            AssetMimeType::AudioMpeg => 'mp3',
            AssetMimeType::AudioXwav => 'wav',
            default                  => throw new ValueError(
                "Asset mime type '{$mimeType->value}' is not supported."
            )
        };
    }


    /** @throws Exception */
    public function generateMissingVideoAssets(Video $video): void
    {
        if ($video->getSourceType() === VideoSourceType::Undefined) {
            throw new Exception(
                "Source of video '{$video->getId()}' is undefined."
            );
        }

        if ($video->getSourceType() === VideoSourceType::Upload) {
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

        if (!$video->hasAssetForAnalyticsWidgetMp4()) {
            $this->generateVideoAssetForAnalyticsWidgetMp4($video);
        }

        if (!$video->hasAssetFullWebm()) {
            $this->generateVideoAssetFullWebm($video);
        }
    }

    /**
     * @throws Exception
     */
    public function generateVideoAssetPosterStillWebp(Video $video): void
    {
        $this->createFilesystemStructureForVideoAssets($video);

        $sourcePath = match ($video->getSourceType()) {
            VideoSourceType::RecordingSession => $this->getVideoChunkContentStorageFilePath(
                $video->getRecordingSession()->getRecordingSessionVideoChunks()->first()
            ),

            VideoSourceType::Upload => $this
                ->getContentStoragePathForVideoUpload($video->getVideoUpload()),

            VideoSourceType::InternallyCreated => $this
                ->getVideoAssetOriginalFilePath($video),

            VideoSourceType::Undefined => throw new Exception()
        };

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
            $process->setTimeout(60 * 30);
            $process->run();

            clearstatcache();

            if (!file_exists(
                $this->getVideoPosterStillAssetFilePath(
                    $video,
                    AssetMimeType::ImageWebp))
            ) {
                if ($video->getSourceType() === VideoSourceType::RecordingSession) {
                    $this
                        ->logger
                        ->info(
                            "Failed to generate video asset 'poster still webp' for video {$video->getId()} from recording session {$video->getRecordingSession()->getId()} using commandline \"{$process->getCommandLine()}\". Command error output was '{$process->getErrorOutput()}'."
                        );
                }

                if ($video->getSourceType() === VideoSourceType::Upload) {
                    $this
                        ->logger
                        ->info(
                            "Failed to generate video asset 'poster still webp' for video {$video->getId()} from video upload {$video->getVideoUpload()->getId()} using commandline \"{$process->getCommandLine()}\". Command error output was '{$process->getErrorOutput()}'."
                        );
                }

                if ($video->getSourceType() === VideoSourceType::InternallyCreated) {
                    $this
                        ->logger
                        ->info(
                            "Failed to generate video asset 'poster still webp' for internally created video {$video->getId()} using commandline \"{$process->getCommandLine()}\". Command error output was '{$process->getErrorOutput()}'."
                        );
                }

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

    /**
     * @throws Exception
     */
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

        $resampledMaxWidth = 720;
        $resampledMaxHeight = 720;

        $dstImageRatio = $posterStillWidth / $posterStillHeight;

        if ($resampledMaxWidth / $resampledMaxHeight > $dstImageRatio) {
            $resampledWidth = $resampledMaxHeight * $dstImageRatio;
            $resampledHeight = $resampledMaxHeight;
        } else {
            $resampledHeight = $resampledMaxWidth/$dstImageRatio;
            $resampledWidth = $resampledMaxWidth;
        }
        $resampledImage = imagecreatetruecolor(
            $resampledWidth,
            $resampledHeight
        );

        imagecopyresampled(
            $resampledImage,
            $dstImage,
            0,
            0,
            0,
            0,
            $resampledWidth,
            $resampledHeight,
            $posterStillWidth,
            $posterStillHeight
        );

        imagepng(
            $resampledImage,
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

    /**
     * @throws Exception
     */
    public function generateVideoAssetPosterAnimatedWebp(Video $video): void
    {
        $this->createFilesystemStructureForVideoAssets($video);

        $sourcePath = match ($video->getSourceType()) {
            VideoSourceType::RecordingSession => $this->getVideoChunkContentStorageFilePath(
                $video->getRecordingSession()->getRecordingSessionVideoChunks()->first()
            ),

            VideoSourceType::Upload => $this
                ->getContentStoragePathForVideoUpload($video->getVideoUpload()),

            VideoSourceType::InternallyCreated => $this
                ->getVideoAssetOriginalFilePath($video),

            VideoSourceType::Undefined => throw new Exception()
        };

        $process = new Process(
            [
                'ffmpeg',

                # seek input to position '1 second'
                '-ss',
                '1',

                # read 3 seconds from source
                '-t',
                '3',

                '-i',
                $sourcePath,

                '-vf',
                'scale=520:-1',

                # framerate
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
        $process->setTimeout(60 * 30);
        $process->run();

        if ($process->isSuccessful()) {
            $video->setHasAssetPosterAnimatedWebp(true);

            // See https://trac.ffmpeg.org/ticket/4907
            // ffmpeg/ffprobe can encode animated WebP files, but cannot decode them,
            // which is why we simply use the width and height of the still image WebP asset
            $video->setAssetPosterAnimatedWebpWidth($video->getAssetPosterStillWebpWidth());
            $video->setAssetPosterAnimatedWebpHeight($video->getAssetPosterStillWebpHeight());

            $this->entityManager->persist($video);
            $this->entityManager->flush();
        }
    }

    /**
     * @throws Exception
     */
    private function generateVideoAssetPosterAnimatedGif(Video $video): void
    {
        $this->createFilesystemStructureForVideoAssets($video);

        $sourcePath = match ($video->getSourceType()) {
            VideoSourceType::RecordingSession => $this->getVideoChunkContentStorageFilePath(
                $video->getRecordingSession()->getRecordingSessionVideoChunks()->first()
            ),

            VideoSourceType::Upload => $this
                ->getContentStoragePathForVideoUpload($video->getVideoUpload()),

            VideoSourceType::InternallyCreated => $this
                ->getVideoAssetOriginalFilePath($video),

            VideoSourceType::Undefined => throw new Exception()
        };

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
        $process->setTimeout(60 * 30);
        $process->run();

        if ($process->isSuccessful()) {
            $video->setHasAssetPosterAnimatedGif(true);
            $this->entityManager->persist($video);
            $this->entityManager->flush();
        }
    }


    /**
     * @throws Exception
     */
    private function generateAssetOriginalForRecordingSession(Video $video): void
    {
        if (is_null($video->getRecordingSession())) {
            throw new ValueError("Video '{$video->getId()}' entity without a recording session entity.");
        }

        if (is_null($video->getAssetOriginalMimeType())) {
            if (sizeof($video->getRecordingSession()->getRecordingSessionVideoChunks()) === 0) {
                throw new ValueError(
                    "Recording session '{$video->getRecordingSession()->getId()}' of video '{$video->getId()}' does not have any video chunks."
                );
            }
            $chunksMimeType = $this->getRecordingSessionChunksMimeType($video->getRecordingSession());

            if (is_null($chunksMimeType)) {
                throw new ValueError(
                    "Could not map mime type value '{$video->getRecordingSession()->getRecordingSessionVideoChunks()[0]->getMimeType()}' of chunk '{$video->getRecordingSession()->getRecordingSessionVideoChunks()[0]->getId()}' of recording session '{$video->getRecordingSession()->getId()}' of video '{$video->getId()}' to a mime type that we know."
                );
            } else {
                $video->setAssetOriginalMimeType($chunksMimeType);
                $this->entityManager->persist($video);
                $this->entityManager->flush();
            }
        }

        $this->createFilesystemStructureForVideoAssets($video);

        $success = $this->concatenateChunksIntoFile(
            $video->getRecordingSession(),
            $this->getVideoAssetOriginalFilePath(
                $video
            )
        );

        if ($success) {
            $video->setHasAssetOriginal(true);

            $video->setAssetOriginalFps(
                $this->probeForVideoAssetFps(
                    $this->getVideoAssetOriginalFilePath($video)
                )
            );

            $video->setAssetOriginalSeconds(
                $this->probeForVideoAssetSeconds(
                    $this->getVideoAssetOriginalFilePath($video)
                )
            );

            $video->setAssetOriginalWidth(
                $this->probeForVideoAssetWidth(
                    $this->getVideoAssetOriginalFilePath($video)
                )
            );

            $video->setAssetOriginalHeight(
                $this->probeForVideoAssetHeight(
                    $this->getVideoAssetOriginalFilePath($video)
                )
            );

            $this->entityManager->persist($video);
            $this->entityManager->flush();
        }
    }

    /**
     * @throws Exception
     */
    private function generateVideoAssetFullMp4(
        Video $video
    ): void
    {
        if (   $video->hasAssetOriginal()
            && $video->getAssetOriginalMimeType() === AssetMimeType::VideoMp4
        ) {
            $fs = new Filesystem();
            $fs->copy(
                $this->getVideoAssetOriginalFilePath($video),
                $this->getVideoFullAssetFilePath($video, AssetMimeType::VideoMp4)
            );

            $video->setHasAssetFullMp4(true);

            $video->setAssetFullMp4Width(
                $video->getAssetOriginalWidth()
            );
            $video->setAssetFullMp4Height(
                $video->getAssetOriginalHeight()
            );
            $video->setAssetFullMp4Seconds(
                $video->getAssetOriginalSeconds()
            );
            $video->setAssetFullMp4Fps(
                $video->getAssetOriginalFps()
            );

            $this->entityManager->persist($video);
            $this->entityManager->flush();

            return;
        }

        if ($video->getSourceType() === VideoSourceType::RecordingSession) {
            if (!$video->hasAssetOriginal()) {
                $this->generateAssetOriginalForRecordingSession($video);
            }

            $sourceWidth = $video->getAssetOriginalWidth();
            $sourceHeight = $video->getAssetOriginalHeight();

            $sourcePath = $this->getVideoAssetOriginalFilePath(
                $video
            );

        } elseif ($video->getSourceType() === VideoSourceType::Upload) {
            $sourcePath = $this->getContentStoragePathForVideoUpload($video->getVideoUpload());

            $sourceWidth = $this->probeForVideoAssetWidth(
                $this->getContentStoragePathForVideoUpload($video->getVideoUpload())
            );

            $sourceHeight = $this->probeForVideoAssetHeight(
                $this->getContentStoragePathForVideoUpload($video->getVideoUpload())
            );

            // iOS stores a 320x480 video as 480x320 and "rotation of -90.00 degrees"
            if ($this->videoDimensionsAreSideways(
                $this->getContentStoragePathForVideoUpload($video->getVideoUpload()))
            ) {
                $tmp = $sourceWidth;
                $sourceWidth = $sourceHeight;
                $sourceHeight = $tmp;
            }
        } elseif ($video->getSourceType() === VideoSourceType::InternallyCreated) {
            $sourcePath = $video->getInternallyCreatedSourceFilePath();
            $sourceWidth = $this->probeForVideoAssetWidth($sourcePath);
            $sourceHeight = $this->probeForVideoAssetHeight($sourcePath);
        } else {
            throw new Exception(
                'Expected source type ' . VideoSourceType::RecordingSession->value . ' or ' . VideoSourceType::Upload->value . ' but got ' . $video->getSourceType()->value . " for video '{$video->getId()}'"
            );
        }

        // The libx264 encoder cannot work with uneven resolutions,
        // we therefore try to feed it even resolutions

        if (!is_null($sourceWidth) && $sourceWidth % 2 !== 0) {
            $sourceWidth += 1;
        }

        if (!is_null($sourceHeight) && $sourceHeight % 2 !== 0) {
            $sourceHeight += 1;
        }

        if (is_null($sourceWidth) && is_null($sourceHeight)) {
            $scaleParam = ''; // in this case, if the input video has an uneven resolution in
                              // one or two of it's dimensions, encoding will fail.
                              // Encoding without a scaleParam is therefore a best-effort approach
        } else {
            $scaleParam = ',scale=';
            if (is_null($sourceWidth)) {
                $scaleParam .= 'iw:';
            } else {
                $scaleParam .= "$sourceWidth:";
            }

            if (is_null($sourceHeight)) {
                $scaleParam .= 'ih';
            } else {
                $scaleParam .= "$sourceHeight";
            }
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
                "format=yuv420p,fps=60$scaleParam",

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
        $process->setIdleTimeout(null);
        $process->setTimeout(60 * 60);
        $process->run();

        if ($process->isSuccessful()) {
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
    }


    /**
     * @throws Exception
     */
    private function generateVideoAssetFullWebm(
        Video $video
    ): void
    {
        if (   $video->hasAssetOriginal()
            && $video->getAssetOriginalMimeType() === AssetMimeType::VideoWebm
        ) {
            $fs = new Filesystem();
            $fs->copy(
                $this->getVideoAssetOriginalFilePath($video),
                $this->getVideoFullAssetFilePath($video, AssetMimeType::VideoWebm)
            );

            $video->setHasAssetFullWebm(true);

            $video->setAssetFullWebmWidth(
                $video->getAssetOriginalWidth()
            );
            $video->setAssetFullWebmHeight(
                $video->getAssetOriginalHeight()
            );
            $video->setAssetFullWebmSeconds(
                $video->getAssetOriginalSeconds()
            );
            $video->setAssetFullWebmFps(
                $video->getAssetOriginalFps()
            );

            $this->entityManager->persist($video);
            $this->entityManager->flush();

            return;
        }


        $sourcePath = null;

        if ($video->hasAssetOriginal()) {
            $sourcePath = $this->getVideoAssetOriginalFilePath($video);
        }

        if ($video->hasAssetFullMp4()) {
            $sourcePath = $this->getVideoFullAssetFilePath($video, AssetMimeType::VideoMp4);
        }

        if (!is_null($sourcePath)) {
            $process = new Process(
                [
                    'ffmpeg',

                    '-i',
                    $sourcePath,

                    # Row based multithreading: https://trac.ffmpeg.org/wiki/Encode/VP9#rowmt
                    '-row-mt',
                    '1',

                    '-c:v',
                    'libvpx-vp9',

                    # Constant quality: https://trac.ffmpeg.org/wiki/Encode/VP9#constantq
                    '-crf',
                    '31',
                    '-b:v',
                    '0',

                    # Frame rate
                    '-vf',
                    'fps=60',

                    '-c:a',
                    'libopus',

                    '-y',
                    $this->getVideoFullAssetFilePath(
                        $video,
                        AssetMimeType::VideoWebm
                    )
                ]
            );
            $process->setIdleTimeout(null);
            $process->setTimeout(60 * 60);
            $process->run();

            if ($process->isSuccessful()) {
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
        }
    }

    /**
     * @throws Exception
     */
    private function generateVideoAssetForAnalyticsWidgetMp4(
        Video $video
    ): void
    {
        if ($video->getSourceType() === VideoSourceType::RecordingSession) {
            if (!$video->hasAssetOriginal()) {
                $this->generateAssetOriginalForRecordingSession($video);
            }

            $sourceWidth = $video->getAssetOriginalWidth();
            $sourceHeight = $video->getAssetOriginalHeight();

            $sourcePath = $this->getVideoAssetOriginalFilePath($video);

        } elseif ($video->getSourceType() === VideoSourceType::Upload) {
            $sourcePath = $this->getContentStoragePathForVideoUpload($video->getVideoUpload());

            $sourceWidth = $this->probeForVideoAssetWidth(
                $this->getContentStoragePathForVideoUpload($video->getVideoUpload())
            );

            $sourceHeight = $this->probeForVideoAssetHeight(
                $this->getContentStoragePathForVideoUpload($video->getVideoUpload())
            );
        } elseif ($video->getSourceType() === VideoSourceType::InternallyCreated) {
            if (!$video->hasAssetOriginal()) {
                $this->createAssetOriginalForInternallyCreatedVideo($video);
            }
            $sourceWidth = $video->getAssetOriginalWidth();
            $sourceHeight = $video->getAssetOriginalHeight();
            $sourcePath = $this->getVideoAssetOriginalFilePath($video);
        } else {
            throw new Exception("Source type '{$video->getSourceType()->value}' of video '{$video->getId()}' is not supported.");
        }

        $sourceHeight = (int)floor($sourceHeight / ($sourceWidth / 320));
        $sourceWidth = 320;

        // The libx264 encoder cannot work with uneven resolutions,
        // we therefore try to feed it even resolutions

        if (!is_null($sourceWidth) && $sourceWidth % 2 !== 0) {
            $sourceWidth += 1;
        }

        if (!is_null($sourceHeight) && $sourceHeight % 2 !== 0) {
            $sourceHeight += 1;
        }

        if (is_null($sourceWidth) && is_null($sourceHeight)) {
            $scaleParam = ''; // in this case, if the input video has an uneven resolution in
            // one or two of it's dimensions, encoding will fail.
            // Encoding without a scaleParam is therefore a best-effort approach
        } else {
            $scaleParam = ',scale=';
            if (is_null($sourceWidth)) {
                $scaleParam .= 'iw:';
            } else {
                $scaleParam .= "$sourceWidth:";
            }

            if (is_null($sourceHeight)) {
                $scaleParam .= 'ih';
            } else {
                $scaleParam .= "$sourceHeight";
            }
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

                '-preset',
                'ultrafast',

                '-crf',
                '40',

                '-vf',
                "format=yuv420p,fps=1$scaleParam",

                '-c:a',
                'aac',

                '-movflags',
                '+faststart',

                '-y',
                $this->getVideoForAnalyticsWidgetAssetFilePath(
                    $video,
                    AssetMimeType::VideoMp4
                )
            ]
        );
        $process->setIdleTimeout(null);
        $process->setTimeout(60 * 30);
        $process->run();

        if ($process->isSuccessful()) {
            $video->setHasAssetForAnalyticsWidgetMp4(true);

            $this->entityManager->persist($video);
            $this->entityManager->flush();
        }
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
    private function videoDimensionsAreSideways(string $filepath): bool
    {
        $process = new Process(
            [
                'ffprobe',

                '-loglevel',
                'error',

                '-select_streams',
                'v:0',

                '-show_entries',
                'side_data=rotation',

                '-of',
                'default=nw=1:nk=1',

                $filepath
            ]
        );
        $process->setTimeout(60 * 10);
        $process->run();

        $output = trim($process->getOutput());

        return $output === '-90'
            || $output === '90'
            || $output === '-270'
            || $output === '270'
            ;
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

    /** @throws ValueError */
    public function getVideoFullAssetFilePath(
        Video         $video,
        AssetMimeType $mimeType
    ): string
    {
        if (is_null($video->getId())) {
            throw new ValueError('Video must have an ID to get its full asset file path.');
        }

        return $this->filesystemService->getPublicWebfolderGeneratedContentPath(
            [
                self::VIDEO_ASSETS_SUBFOLDER_NAME,
                $video->getId(),
                'full.' . $this->mimeTypeToFileSuffix($mimeType)
            ]
        );
    }

    private function getVideoForAnalyticsWidgetAssetFilePath(
        Video         $video,
        AssetMimeType $mimeType
    ): string
    {
        return $this->filesystemService->getPublicWebfolderGeneratedContentPath(
            [
                self::VIDEO_ASSETS_SUBFOLDER_NAME,
                $video->getId(),
                'for-analytics-widget.' . $this->mimeTypeToFileSuffix($mimeType)
            ]
        );
    }

    private function getVideoAssetOriginalFilePath(
        Video $video
    ): string
    {
        return $this->filesystemService->getPublicWebfolderGeneratedContentPath(
            [
                self::VIDEO_ASSETS_SUBFOLDER_NAME,
                $video->getId(),
                "{$video->getId()}_original.{$this->mimeTypeToFileSuffix($video->getAssetOriginalMimeType())}"
            ]
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function concatenateChunksIntoFile(
        RecordingSession $recordingSession,
        string $targetFilePath
    ): bool
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

        $stmt->bindValue(':rsid', $recordingSession->getId());
        $resultSet = $stmt->executeQuery();

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

        return $process->isSuccessful();
    }

    /**
     * @throws Exception
     */
    public function checkAndHandleVideoAssetGenerationForUser(
        User $user,
        bool $basicAssetsForAllVideosAreRequiredImmediately = false,
        bool $basicAssetsForLatestVideoAreRequiredImmediately = false
    ): void
    {
        $this->entityManager->refresh($user);

        /** @var Video[] $videos */
        $videos = $user->getVideos()->toArray();

        usort($videos, fn(Video $a, Video $b) => $a->getCreatedAt() <=> $b->getCreatedAt());
        rsort($videos);

        foreach ($videos as $key => $video) {
            $basicAssetsAreRequiredImmediately = $basicAssetsForAllVideosAreRequiredImmediately;
            if ($key === 0 && $basicAssetsForLatestVideoAreRequiredImmediately) {
                $basicAssetsAreRequiredImmediately = true;
            }
            $this->checkAndHandleVideoAssetGenerationForVideo(
                $video,
                $basicAssetsAreRequiredImmediately
            );
        }
    }

    /**
     * @throws Exception
     */
    private function checkAndHandleVideoAssetGenerationForVideo(
        Video $video,
        bool $basicAssetsAreRequiredImmediately = true
    ): void
    {
        $this->logger->debug("Checking video '{$video->getId()}' for missing assets.");

        $forceGenerateMissingAssetsCommand = false;

        if ($video->getSourceType() === VideoSourceType::RecordingSession) {
           if ($video->getRecordingSession()->isFinished()) {
               if (!$video->hasAssetOriginal()) {
                   $this->generateAssetOriginalForRecordingSession($video);

                   if (   !$video->hasAssetFullMp4()
                       && $video->getAssetOriginalMimeType() === AssetMimeType::VideoMp4)
                   {
                       $this->generateVideoAssetFullMp4($video);
                   }

                   if (   !$video->hasAssetFullWebm()
                       && $video->getAssetOriginalMimeType() === AssetMimeType::VideoWebm)
                   {
                       $this->generateVideoAssetFullWebm($video);
                   }
               }
           }
        }

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
                    new GenerateMissingVideoAssetsCommandSymfonyMessage($video)
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
        UploadComplete $event,
        ?string        $videoFolderId
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

        $videoFolder = null;
        if (!is_null($videoFolderId)) {
            $videoFolder = $this->entityManager->find(VideoFolder::class, $videoFolderId);
        }
        $video->setVideoFolder($videoFolder);

        $this->entityManager->persist($videoUpload);
        $this->entityManager->persist($video);
        $this->entityManager->flush();

        $this->shortIdService->encodeObject($video);

        $fs = new Filesystem();

        $fs->rename(
            $this->filesystemService->getContentStoragePath(
                [
                    self::UPLOADED_VIDEO_ASSETS_SUBFOLDER_NAME,
                    $user->getId(),
                    $videoUpload->getFileName()
                ]
            ),
            $this->getContentStoragePathForVideoUpload($videoUpload)
        );

        $this->generateVideoAssetPosterStillWebp($video);
        $this->generateVideoAssetPosterAnimatedWebp($video);

        $this->messageBus->dispatch(
            new ClearTusCacheCommandSymfonyMessage($user, $token)
        );

        $this->messageBus->dispatch(
            new GenerateMissingVideoAssetsCommandSymfonyMessage($video)
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

    public function getContentStoragePathForVideoUpload(
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


    private function getAssetMimeTypeForFilePathExtension(
        string $filePath
    ): ?AssetMimeType
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        foreach (AssetMimeType::cases() as $assetMimeType) {
            if (self::mimeTypeToFileSuffix($assetMimeType) === $extension) {
                return $assetMimeType;
            }
        }

        return null;
    }


    /**
     * @throws Exception
     */
    public function createAssetOriginalForInternallyCreatedVideo(
        Video $video
    ): void
    {
        if ($video->getSourceType() !== VideoSourceType::InternallyCreated) {
            throw new ValueError("Video '{$video->getId()}' is not internally created.");
        }

        $sourceFileMimeType = $this->getAssetMimeTypeForFilePathExtension(
            $video->getInternallyCreatedSourceFilePath()
        );

        if (is_null($sourceFileMimeType)) {
            throw new ValueError(
                "Could not determine asset mime type for internally created source file path '{$video->getInternallyCreatedSourceFilePath()}' of video '{$video->getId()}'."
            );
        }

        $video->setAssetOriginalMimeType($sourceFileMimeType);

        $this->createFilesystemStructureForVideoAssets($video);

        $fs = new Filesystem();

        $fs->rename(
            $video->getInternallyCreatedSourceFilePath(),
            $this->getVideoAssetOriginalFilePath(
                $video
            )
        );

        $video->setHasAssetOriginal(true);

        $video->setAssetOriginalFps(
            $this->probeForVideoAssetFps(
                $this->getVideoAssetOriginalFilePath($video)
            )
        );

        $video->setAssetOriginalSeconds(
            $this->probeForVideoAssetSeconds(
                $this->getVideoAssetOriginalFilePath($video)
            )
        );

        $video->setAssetOriginalWidth(
            $this->probeForVideoAssetWidth(
                $this->getVideoAssetOriginalFilePath($video)
            )
        );

        $video->setAssetOriginalHeight(
            $this->probeForVideoAssetHeight(
                $this->getVideoAssetOriginalFilePath($video)
            )
        );

        $this->entityManager->persist($video);
        $this->entityManager->flush();
    }
}
