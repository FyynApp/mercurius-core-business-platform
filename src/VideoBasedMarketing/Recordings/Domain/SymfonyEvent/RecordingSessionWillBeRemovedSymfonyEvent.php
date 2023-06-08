<?php

namespace App\VideoBasedMarketing\Recordings\Domain\SymfonyEvent;

use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;

readonly class RecordingSessionWillBeRemovedSymfonyEvent
{
    public function __construct(
        public RecordingSession $recordingSession
    )
    {
    }
}
