<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Enum;

enum VideoSource: string
{
    case Recording = 'recording';
    case Upload = 'upload';
    case InternallyCreated = 'internallyCreated';
}
