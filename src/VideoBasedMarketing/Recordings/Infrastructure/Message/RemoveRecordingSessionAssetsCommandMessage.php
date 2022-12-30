<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\Message;

use App\Shared\Infrastructure\Message\AsyncMessageInterface;

class RemoveRecordingSessionAssetsCommandMessage
    implements AsyncMessageInterface
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
