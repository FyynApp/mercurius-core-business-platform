<?php

namespace App\MessageHandler\Feature\Recordings;

use App\Message\Feature\Recordings\RecordingSessionFinished;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RecordingSessionFinishedHandler
{
    public function __invoke(RecordingSessionFinished $sessionFinished)
    {
        // tbd
    }
}
