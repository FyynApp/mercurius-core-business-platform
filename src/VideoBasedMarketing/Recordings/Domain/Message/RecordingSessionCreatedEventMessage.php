<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Message;

use App\Shared\Domain\Message\UserOwnedEntityLifecycleEventMessageInterface;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;


class RecordingSessionCreatedEventMessage
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
