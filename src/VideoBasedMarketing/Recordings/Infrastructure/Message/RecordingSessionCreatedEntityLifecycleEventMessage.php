<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\Message;

use App\Entity\Feature\Recordings\RecordingSession;
use App\Shared\Domain\Message\UserOwnedEntityLifecycleEventMessageInterface;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;


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
