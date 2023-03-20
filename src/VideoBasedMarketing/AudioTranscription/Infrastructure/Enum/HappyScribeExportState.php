<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\Enum;

enum HappyScribeExportState: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Ready = 'ready';
    case Expired = 'expired';
    case Failed = 'failed';
}
