<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\Enum;

enum HappyScribeTranslationTaskState: string
{
    case Initial = 'initial';
    case Working = 'working';
    case Failed = 'failed';
    case Done = 'done';
}
