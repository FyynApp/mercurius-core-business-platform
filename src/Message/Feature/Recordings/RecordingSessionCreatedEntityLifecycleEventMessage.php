<?php

namespace App\Message\Feature\Recordings;

use App\Entity\Feature\Recordings\RecordingSession;
use App\Entity\UserOwnedEntityInterface;
use App\Message\UserOwnedEntityLifecycleEventMessageInterface;


class RecordingSessionCreatedEntityLifecycleEventMessage
    implements UserOwnedEntityLifecycleEventMessageInterface
{
    private RecordingSession $recordingSession;

    public function __construct(
        RecordingSession $recordingSession
    )
    {
        $this->recordingSession = $recordingSession;
    }

    public function getRecordingSession(): RecordingSession
    {
        return $this->recordingSession;
    }

    public function getEntity(): UserOwnedEntityInterface
    {
        return $this->getRecordingSession();
    }
}
