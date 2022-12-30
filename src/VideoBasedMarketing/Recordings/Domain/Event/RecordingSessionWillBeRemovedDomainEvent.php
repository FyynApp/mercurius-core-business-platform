<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Event;

use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;

readonly class RecordingSessionWillBeRemovedDomainEvent
{
    public function __construct(
        public RecordingSession $recordingSession
    )
    {}
}
