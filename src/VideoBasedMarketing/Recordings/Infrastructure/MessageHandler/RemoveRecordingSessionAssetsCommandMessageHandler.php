<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\MessageHandler;

use App\VideoBasedMarketing\Recordings\Infrastructure\Message\RemoveRecordingSessionAssetsCommandMessage;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;


#[AsMessageHandler]
class RemoveRecordingSessionAssetsCommandMessageHandler
{
    private RecordingsInfrastructureService $recordingsInfrastructureService;

    public function __construct(
        RecordingsInfrastructureService $recordingsInfrastructureService,
    )
    {
        $this->recordingsInfrastructureService = $recordingsInfrastructureService;
    }

    public function __invoke(
        RemoveRecordingSessionAssetsCommandMessage $message
    ): void
    {
        $this->recordingsInfrastructureService->removeRecordingSessionAssetsById(
            $message->getRecordingSessionId()
        );
    }
}
