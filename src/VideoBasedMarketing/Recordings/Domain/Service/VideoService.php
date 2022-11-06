<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Service;

use App\Entity\Feature\Recordings\AssetMimeType;
use App\Entity\Feature\Recordings\RecordingSession;
use App\Entity\Feature\Recordings\Video;
use App\Shared\Infrastructure\Service\FilesystemService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Presentationpages\Domain\Service\PresentationpagesService;
use App\VideoBasedMarketing\Recordings\Infrastructure\Message\GenerateMissingAssetsCommandMessage;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


class VideoService
{
    private const ASSETS_SUBFOLDER_NAME = 'video-assets';

    private EntityManagerInterface $entityManager;

    private FilesystemService $filesystemService;

    private RecordingSessionService $recordingSessionService;

    private RouterInterface $router;

    private MessageBusInterface $messageBus;

    private LoggerInterface $logger;

    private TranslatorInterface $translator;

    private PresentationpagesService $presentationpagesService;


    public function __construct(
        EntityManagerInterface   $entityManager,
        FilesystemService        $filesystemService,
        RecordingSessionService  $recordingSessionService,
        RouterInterface          $router,
        MessageBusInterface      $messageBus,
        LoggerInterface          $logger,
        TranslatorInterface      $translator,
        PresentationpagesService $presentationpagesService
    )
    {
        $this->entityManager = $entityManager;
        $this->filesystemService = $filesystemService;
        $this->recordingSessionService = $recordingSessionService;
        $this->router = $router;
        $this->messageBus = $messageBus;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->presentationpagesService = $presentationpagesService;
    }


    /**
     * @return Video[]
     */
    public function getAvailableVideos(User $user): array
    {
        /** @var Video[] $allVideos */
        $allVideos = $user->getVideos()
                    ->toArray();

        $videos = [];
        foreach ($allVideos as $video) {
            if (!$video->isDeleted()) {
                $videos[] = $video;
            }
        }

        rsort($videos);

        return $videos;
    }


    public function getPosterStillAssetUrl(Video $video): string
    {
        if ($video->hasAssetPosterStillWebp()) {
            return $this->router->generate(
                'feature.recordings.video.poster_still.asset',
                ['videoId' => $video->getId(), 'extension' => $this->mimeTypeToFileSuffix(AssetMimeType::ImageWebp)]
            );
        } else {
            return $this->router->generate('feature.recordings.video.missing_poster_asset_placeholder');
        }
    }

    public function getPosterAnimatedAssetUrl(Video $video): string
    {
        if ($video->hasAssetPosterAnimatedWebp()) {
            return $this->router->generate(
                'feature.recordings.video.poster_animated.asset',
                ['videoId' => $video->getId(), 'extension' => $this->mimeTypeToFileSuffix(AssetMimeType::ImageWebp)]
            );
        } else {
            return $this->router->generate('feature.recordings.video.missing_poster_asset_placeholder');
        }
    }

    public function getFullAssetUrl(Video $video): string
    {
        if ($video->hasAssetFullMp4()) {
            return $this->router->generate(
                'feature.recordings.video.full.asset',
                ['videoId' => $video->getId(), 'extension' => $this->mimeTypeToFileSuffix(AssetMimeType::VideoMp4)]
            );
        } elseif ($video->hasAssetFullWebm()) {
            return $this->router->generate(
                'feature.recordings.video.full.asset',
                ['videoId' => $video->getId(), 'extension' => $this->mimeTypeToFileSuffix(AssetMimeType::VideoWebm)]
            );
        } else {
            return $this->router->generate('feature.recordings.video.missing_full_asset_placeholder');
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
    public function createVideoEntityForFinishedRecordingSession(RecordingSession $recordingSession): Video
    {
        if (!$recordingSession->isFinished()) {
            throw new InvalidArgumentException("Recording session '{$recordingSession->getId()} is not finished'.");
        }

        $video = new Video($recordingSession->getUser());
        $video->setTitle(
            $this->translator->trans(
                'feature.recordings.new_video_title',
                ['{num}' => $recordingSession->getUser()->getVideos()->count() + 1]
            )
        );

        $video->setRecordingSession($recordingSession);
        $recordingSession->setVideo($video);
        $recordingSession->setIsFinished(true);
        $this->entityManager->persist($video);
        $this->entityManager->persist($recordingSession);
        $this->entityManager->flush();

        $templates = $this
            ->presentationpagesService
            ->getVideoOnlyPresentationpageTemplatesForUser(
                $recordingSession->getUser()
            );

        $video->setVideoOnlyPresentationpageTemplate($templates[0]);

        if (is_null(
            $recordingSession
                ->getRecordingSessionVideoChunks()
                ->first()
        )) {
            throw new Exception("Cannot generate poster assets for video '{$video->getId()}' because its recording session '{$recordingSession->getId()}' does not have any video chunks.");
        }

        $this->generateAssetPosterStillWebp($video);

        $this->generateAssetPosterAnimatedWebp($video);

        // Heavy-lifting stuff like missing video assets generation happens asynchronously
        $this->messageBus->dispatch(new GenerateMissingAssetsCommandMessage($video));

        return $video;
    }


    /** @throws Exception */
    public function generateMissingAssets(Video $video): void
    {
        if (is_null($video->getRecordingSession())) {
            throw new Exception('Need video linked to recording session.');
        }

        $this->createFilesystemStructureForAssets($video);

        if (!$video->hasAssetPosterStillWebp()) {
            $this->generateAssetPosterStillWebp($video);
        }

        if (!$video->hasAssetPosterAnimatedWebp()) {
            $this->generateAssetPosterAnimatedWebp($video);
        }

        if (!$video->hasAssetFullMp4()) {
            $this->generateAssetFullMp4($video);
        }

        if (!$video->hasAssetPosterAnimatedGif()) {
            $this->generateAssetPosterAnimatedGif($video);
        }

        /* Very expensive and likely not needed
        if (!$video->hasAssetFullWebm()) {
            $this->generateAssetFullWebm($video);
        }
        */
    }


    public function deleteVideo(Video $video): void
    {
        $video->setIsDeleted(true);
        $this->entityManager->persist($video);
        $this->entityManager->flush();
    }


    private function generateAssetPosterStillWebp(Video $video): void
    {
        $this->createFilesystemStructureForAssets($video);

        for ($i = 50; $i > 0; $i--) {
            shell_exec("/usr/bin/env ffmpeg -i {$this->recordingSessionService->getVideoChunkContentStorageFilePath($video->getRecordingSession()->getRecordingSessionVideoChunks()->first())} -vf \"select=eq(n\,$i)\" -q:v 70 -y {$this->getPosterStillAssetFilePath($video, AssetMimeType::ImageWebp)}");

            clearstatcache();
            $filesize = filesize($this->getPosterStillAssetFilePath($video, AssetMimeType::ImageWebp));

            if ($filesize > 0) {
                $video->setHasAssetPosterStillWebp(true);
                $this->entityManager->persist($video);
                $this->entityManager->flush();
                break;
            }
        }
    }

    private function generateAssetPosterAnimatedWebp(Video $video): void
    {
        $this->createFilesystemStructureForAssets($video);

        shell_exec("/usr/bin/env ffmpeg -ss 1 -t 3 -i {$this->recordingSessionService->getVideoChunkContentStorageFilePath($video->getRecordingSession()->getRecordingSessionVideoChunks()->first())} -vf scale=520:-1 -r 7 -q:v 80 -loop 0 -y {$this->getPosterAnimatedAssetFilePath($video, AssetMimeType::ImageWebp)}");

        $video->setHasAssetPosterAnimatedWebp(true);
        $this->entityManager->persist($video);
        $this->entityManager->flush();
    }

    private function generateAssetPosterAnimatedGif(Video $video): void
    {
        $this->createFilesystemStructureForAssets($video);

        shell_exec("/usr/bin/env ffmpeg -ss 1 -t 3 -i {$this->recordingSessionService->getVideoChunkContentStorageFilePath($video->getRecordingSession()->getRecordingSessionVideoChunks()->first())} -vf \"fps=7,scale=480:-1:flags=lanczos,split[s0][s1];[s0]palettegen=max_colors=256:reserve_transparent=0[p];[s1][p]paletteuse=dither=none\" -r 7 -q:v 20 -loop 0 -y {$this->getPosterAnimatedAssetFilePath($video, AssetMimeType::ImageGif)}");

        $video->setHasAssetPosterAnimatedGif(true);
        $this->entityManager->persist($video);
        $this->entityManager->flush();
    }


    /**
     * @throws Exception
     */
    private function generateAssetFullWebm(Video $video): void
    {
        shell_exec("/usr/bin/env ffmpeg -f concat -safe 0 -i {$this->recordingSessionService->generateVideoChunksFilesListFile($video->getRecordingSession())} -vf \"fps=60\" -y {$this->getFullAssetFilePath($video, AssetMimeType::VideoWebm)}");

        $video->setHasAssetFullWebm(true);

        $video->setAssetFullWebmFps(
            $this->calculateAssetFullFps(
                $this->getFullAssetFilePath($video, AssetMimeType::VideoWebm)
            )
        );

        $video->setAssetFullWebmSeconds(
            $this->calculateAssetFullSeconds(
                $this->getFullAssetFilePath($video, AssetMimeType::VideoWebm)
            )
        );


        $this->entityManager->persist($video);
        $this->entityManager->flush();
    }

    /**
     * @throws Exception
     */
    private function generateAssetFullMp4(Video $video): void
    {
        shell_exec("/usr/bin/env ffmpeg -f concat -safe 0 -i {$this->recordingSessionService->generateVideoChunksFilesListFile($video->getRecordingSession())} -c:v libx264 -profile:v main -level 4.2 -vf format=yuv420p,fps=60 -c:a aac -movflags +faststart -y {$this->getFullAssetFilePath($video, AssetMimeType::VideoMp4)}");

        $video->setHasAssetFullMp4(true);

        $video->setAssetFullMp4Fps(
            $this->calculateAssetFullFps(
                $this->getFullAssetFilePath($video, AssetMimeType::VideoMp4)
            )
        );

        $video->setAssetFullMp4Seconds(
            $this->calculateAssetFullSeconds(
                $this->getFullAssetFilePath($video, AssetMimeType::VideoMp4)
            )
        );

        $this->entityManager->persist($video);
        $this->entityManager->flush();
    }


    /**
     * @throws Exception
     */
    private function calculateAssetFullFps(string $filepath): float
    {
        $output = shell_exec("/usr/bin/env ffprobe -v error -select_streams v -of default=noprint_wrappers=1:nokey=1 -show_entries stream=r_frame_rate $filepath");

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
    private function calculateAssetFullSeconds(string $filepath): float
    {
        $output = shell_exec("/usr/bin/env ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 $filepath");

        if (is_numeric($output)) {
            return (float)$output;
        } else {
            throw new Exception("Did not get numeric seconds value for file at $filepath.");
        }
    }


    private function createFilesystemStructureForAssets(Video $video): void
    {
        $fs = new Filesystem();
        $fs->mkdir(
            $this->filesystemService->getPublicWebfolderGeneratedContentPath(
                [
                    self::ASSETS_SUBFOLDER_NAME,
                    $video->getId()
                ]
            )
        );
    }

    private function getPosterStillAssetFilePath(
        Video         $video,
        AssetMimeType $mimeType
    ): string
    {
        if ($mimeType !== AssetMimeType::ImageWebp) {
            throw new InvalidArgumentException();
        }

        return $this->filesystemService->getPublicWebfolderGeneratedContentPath(
            [
                self::ASSETS_SUBFOLDER_NAME,
                $video->getId(),
                'poster-still.' . $this->mimeTypeToFileSuffix($mimeType)
            ]
        );
    }

    private function getPosterAnimatedAssetFilePath(
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
                self::ASSETS_SUBFOLDER_NAME,
                $video->getId(),
                'poster-animated.' . $this->mimeTypeToFileSuffix($mimeType)
            ]
        );
    }

    private function getFullAssetFilePath(
        Video         $video,
        AssetMimeType $mimeType
    ): string
    {
        return $this->filesystemService->getPublicWebfolderGeneratedContentPath(
            [
                self::ASSETS_SUBFOLDER_NAME,
                $video->getId(),
                'full.' . $this->mimeTypeToFileSuffix($mimeType)
            ]
        );
    }
}
