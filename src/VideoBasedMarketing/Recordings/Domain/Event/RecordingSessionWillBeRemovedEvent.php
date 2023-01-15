<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Event;

use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;

readonly class RecordingSessionWillBeRemovedEvent
{
    public function __construct(
        public RecordingSession $recordingSession
    )
    {
    }
}
