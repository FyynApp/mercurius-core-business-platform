<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\Recordings\Infrastructure\Message\GenerateMissingVideoAssetsCommandMessage;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;


class VideoAssetGenerationDomainService
{
    private MessageBusInterface $messageBus;

    private RecordingsInfrastructureService $recordingsInfrastructureService;

    private LoggerInterface $logger;

    private CapabilitiesService $capabilitiesService;


    public function __construct(
        MessageBusInterface             $messageBus,
        RecordingsInfrastructureService $recordingsInfrastructureService,
        LoggerInterface                 $logger,
        CapabilitiesService             $capabilitiesService
    )
    {
        $this->messageBus = $messageBus;
        $this->recordingsInfrastructureService = $recordingsInfrastructureService;
        $this->logger = $logger;
        $this->capabilitiesService = $capabilitiesService;
    }

    public function checkAndHandleVideoAssetGeneration(
        User $user
    ): void
    {
        $this
            ->logger
            ->debug("User '{$user->getId()}' has " . sizeof($user->getVideos()) . " videos.");

        foreach ($user->getVideos() as $video) {

            $this->logger->debug("Checking video '{$video->getId()}' for missing assets.");

            if (!$video->hasAssetPosterStillWebp()) {
                $this
                    ->recordingsInfrastructureService
                    ->generateVideoAssetPosterStillWebp($video);
            }

            if (!$video->hasAssetPosterAnimatedWebp()) {
                $this
                    ->recordingsInfrastructureService
                    ->generateVideoAssetPosterAnimatedWebp($video);
            }

            if (   !$video->hasAssetFullMp4()
                || !$video->hasAssetFullWebm()
            ) {
                if ($this->capabilitiesService->canHaveAllVideoAssetsGenerated($user)) {
                    $this->messageBus->dispatch(
                        new GenerateMissingVideoAssetsCommandMessage($video)
                    );
                }
            }
        }
    }
}
