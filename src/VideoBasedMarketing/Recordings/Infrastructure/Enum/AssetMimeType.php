<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\Enum;

enum AssetMimeType: string
{
    case ImageWebp = 'image/webp';
    case ImageGif = 'image/gif';
    case VideoWebm = 'video/webm';
    case VideoMp4 = 'video/mp4';
    case ImagePng = 'image/png';
    case AudioMpeg = 'audio/mpeg';
    case AudioXwav = 'audio/x-wav';
}
