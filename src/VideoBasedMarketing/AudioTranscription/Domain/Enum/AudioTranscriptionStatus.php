<?php

namespace App\VideoBasedMarketing\AudioTranscription\Domain\Enum;

enum AudioTranscriptionStatus: string
{
    case Created = 'created';
    case InProgress = 'inProgress';
    case Done = 'done';
}
