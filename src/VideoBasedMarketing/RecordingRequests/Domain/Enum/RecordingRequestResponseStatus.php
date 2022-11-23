<?php

namespace App\VideoBasedMarketing\RecordingRequests\Domain\Enum;

enum RecordingRequestResponseStatus: string
{
    case UNANSWERED = 'unanswered';
    case ANSWERED = 'answered';
    case WONTANSWER = 'wontanswer';
}
