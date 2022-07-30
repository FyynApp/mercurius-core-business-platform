<?php

namespace App\MessageHandler\Feature\Recordings;

use App\Message\Feature\Recordings\RecordingSessionFinishedMessage;
use App\Service\Feature\Recordings\RecordingSessionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RecordingSessionFinishedMessageHandler
{
    private LoggerInterface $logger;

    private RecordingSessionService $recordingSessionService;

    public function __construct(
        LoggerInterface $logger,
        RecordingSessionService $recordingSessionService
    ) {
        $this->logger = $logger;
        $this->recordingSessionService = $recordingSessionService;
    }

    public function __invoke(RecordingSessionFinishedMessage $message)
    {
        $this->logger->debug("Received RecordingSessionFinishedMessage for recording session {$message->getRecordingSessionId()}.");

        $this->recordingSessionService->generateFullVideo($message->getRecordingSessionId());
    }
}
