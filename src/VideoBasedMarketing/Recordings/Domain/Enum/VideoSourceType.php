<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Enum;

enum VideoSourceType: string
{
    case Undefined = 'undefined';
    case RecordingSession = 'recordingSession';
    case Upload = 'upload';
    case InternallyCreated = 'internallyCreated';
}
