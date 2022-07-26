<?php

namespace App\Message\Feature\Recordings;

use App\Entity\Feature\Recordings\RecordingSession;
use InvalidArgumentException;

class RecordingSessionFinished
{
    private string $recordingSessionId;

    public function __construct(RecordingSession $recordingSession)
    {
        if (is_null($recordingSession->getId())) {
            throw new InvalidArgumentException('recording session needs an id.');
        }
        $this->recordingSessionId = $recordingSession->getId();
    }

    public function getRecordingSessionId(): string
    {
        return $this->recordingSessionId;
    }
}
