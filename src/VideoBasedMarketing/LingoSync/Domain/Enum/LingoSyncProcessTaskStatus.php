<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\Enum;


enum LingoSyncProcessTaskStatus: string
{
    case Initiated = 'initiated';
    case Running = 'running';
    case Finished = 'finished';
    case Errored = 'errored';
    case Stopped = 'stopped';
}
