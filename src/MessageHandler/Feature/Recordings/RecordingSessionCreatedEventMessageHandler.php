<?php

namespace App\MessageHandler\Feature\Recordings;

use App\Message\Feature\Recordings\RecordingSessionCreatedEntityLifecycleEventMessage;
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
        RecordingSessionCreatedEntityLifecycleEventMessage $message
    ): void
    {
        $this->logger->debug('This is the RecordingSessionCreatedEventMessageHandler.');

        // TODO: Write secondary business logic
    }
}
