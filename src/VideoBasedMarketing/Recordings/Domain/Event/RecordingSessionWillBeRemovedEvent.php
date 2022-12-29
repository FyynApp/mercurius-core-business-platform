<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Event;

use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;

class RecordingSessionWillBeRemovedEvent
{
    private string $recordingSessionId;

    public function __construct(
        RecordingSession $recordingSession
    )
    {
        $this->recordingSessionId = $recordingSession->getId();
    }

    public function getRecordingSessionId(): string
    {
        return $this->recordingSessionId;
    }
}
