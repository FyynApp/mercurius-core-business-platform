<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\Message;

class RemoveRecordingSessionAssetsCommandMessage
{
    private string $recordingSessionId;

    public function __construct(
        string $recordingSessionId
    )
    {
        $this->recordingSessionId = $recordingSessionId;
    }

    public function getRecordingSessionId(): string
    {
        return $this->recordingSessionId;
    }
}
