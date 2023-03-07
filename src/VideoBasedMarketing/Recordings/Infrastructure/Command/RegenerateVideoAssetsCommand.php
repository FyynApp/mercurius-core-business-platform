<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\Command;

use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:videobasedmarketing:recordings:infrastructure:regenerate-video-assets',
    description: '',
    aliases: ['regenerate-video-assets']
)]
class RegenerateVideoAssetsCommand
    extends Command
{
    private EntityManagerInterface $entityManager;

    private RecordingsInfrastructureService $recordingsInfrastructureService;

    public function __construct(
        EntityManagerInterface          $entityManager,
        RecordingsInfrastructureService $recordingsInfrastructureService
    )
    {
        $this->entityManager = $entityManager;
        $this->recordingsInfrastructureService = $recordingsInfrastructureService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'videoId',
                InputArgument::REQUIRED,
                'The id of the video for which assets shall be regenerated.'
            );
    }

    protected function execute(
        InputInterface  $input,
        OutputInterface $output
    ): int
    {
        $videoId = $input
            ->getArgument('videoId');

        $video = $this
            ->entityManager
            ->find(Video::class, $videoId);

        $video->setHasAssetFullWebm(false);
        $video->setHasAssetFullMp4(false);
        $video->setHasAssetPosterStillWebp(false);
        $video->setHasAssetPosterAnimatedWebp(false);
        $video->setHasAssetPosterAnimatedGif(false);
        $video->setHasAssetPosterStillWithPlayOverlayForEmailPng(false);
        $video->setHasAssetForAnalyticsWidgetMp4(false);

        #$this->entityManager->persist($video);
        #$this->entityManager->flush();

        $this->recordingsInfrastructureService->generateMissingVideoAssets($video);

        return 0;
    }
}
