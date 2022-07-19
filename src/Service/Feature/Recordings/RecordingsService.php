<?php

namespace App\Service\Feature\Recordings;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Recordings\RecordingSession;

class RecordingsService
{
    /** @return RecordingSession[] */
    public function getRecordingSessionsWithFullVideo(User $user): array
    {
        $results = [];
        foreach ($user->getRecordingSessions() as $recordingSession) {
            if (!is_null($recordingSession->getRecordingSessionFullVideo()))
                $results[] = $recordingSession;
        }
        return $results;
    }
}
