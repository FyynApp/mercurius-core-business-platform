<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\Enum;

enum HappyScribeTranscriptionState: string
{
    case Initial = 'initial';
    case Ingesting = 'ingesting';
    case AutomaticTranscribing = 'automatic_transcribing';
    case AutomaticDone = 'automatic_done';
    case Aligning = 'aligning';
    case Locked = 'locked';
    case Failed = 'failed';
    case Demo = 'demo';
}
