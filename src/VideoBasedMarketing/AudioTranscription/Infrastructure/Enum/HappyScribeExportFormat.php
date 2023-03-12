<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\Enum;

enum HappyScribeExportFormat: string
{
    case Txt = 'txt';
    case Docx = 'docx';
    case Pdf = 'pdf';
    case Srt = 'srt';
    case Vtt = 'vtt';
    case Stl = 'stl';
    case Avid = 'avid';
    case Html = 'html';
    case Premiere = 'premiere';
    case Maxqda = 'maxqda';
    case Json = 'json';
    case Fcp = 'fcp';
}
