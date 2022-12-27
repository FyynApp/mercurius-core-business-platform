<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Recordings\Infrastructure\Message\GenerateMissingVideoAssetsCommandMessage;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use Symfony\Component\Messenger\MessageBusInterface;


class VideoAssetGenerationDomainService
{
    private MessageBusInterface $messageBus;

    private RecordingsInfrastructureService $recordingsInfrastructureService;

    public function __construct(
        MessageBusInterface             $messageBus,
        RecordingsInfrastructureService $recordingsInfrastructureService
    )
    {
        $this->messageBus = $messageBus;
        $this->recordingsInfrastructureService = $recordingsInfrastructureService;
    }

    public function checkAndHandleVideoAssetGeneration(
        User $user
    ): void
    {
        foreach ($user->getVideos() as $video) {

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
                if ($user->isRegistered() && $user->isVerified()) {
                    $this->messageBus->dispatch(
                        new GenerateMissingVideoAssetsCommandMessage($video)
                    );
                }
            }
        }
    }
}
