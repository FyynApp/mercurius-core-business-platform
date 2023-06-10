<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Enum;

enum VideoSourceType: string
{
    case Undefined = 'undefined';
    case Recording = 'recording';
    case Upload = 'upload';
    case InternallyCreated = 'internallyCreated';
}
