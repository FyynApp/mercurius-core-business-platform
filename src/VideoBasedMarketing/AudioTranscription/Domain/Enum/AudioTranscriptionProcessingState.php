<?php

namespace App\VideoBasedMarketing\AudioTranscription\Domain\Enum;

enum AudioTranscriptionProcessingState: string
{
    case Started = 'started';
    case PartlyFinished = 'partlyFinished';
    case Finished = 'finished';
    case Failed = 'failed';
}
