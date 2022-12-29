<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\MessageHandler;

use App\VideoBasedMarketing\Recordings\Infrastructure\Message\RemoveRecordingSessionAssetsCommandMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;


#[AsMessageHandler]
class RemoveRecordingSessionAssetsCommandMessageHandler
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger
    )
    {
        $this->logger = $logger;
    }

    public function __invoke(
        RemoveRecordingSessionAssetsCommandMessage $message
    ): void
    {

    }
}
