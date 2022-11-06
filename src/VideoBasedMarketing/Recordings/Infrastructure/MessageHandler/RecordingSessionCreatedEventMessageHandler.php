<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\MessageHandler;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;


#[AsMessageHandler]
class RecordingSessionCreatedEventMessageHandler
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger
    )
    {
        $this->logger = $logger;
    }

    public function __invoke(
        \App\VideoBasedMarketing\Recordings\Infrastructure\Message\RecordingSessionCreatedEntityLifecycleEventMessage $message
    ): void
    {
        $this->logger->debug('This is the RecordingSessionCreatedEventMessageHandler.');

        // TODO: Write secondary business logic
    }
}
