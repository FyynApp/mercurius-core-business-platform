<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Message;

use App\Shared\Domain\Message\UserOwnedEntityLifecycleEventMessageInterface;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;


class RecordingSessionRemovedEventMessage
    implements UserOwnedEntityLifecycleEventMessageInterface
{
    private RecordingSession $recordingSession;

    public function __construct(
        RecordingSession $recordingSession
    )
    {
        $this->recordingSession = $recordingSession;
    }

    public function getEntity(): RecordingSession
    {
        return $this->recordingSession;
    }
}
