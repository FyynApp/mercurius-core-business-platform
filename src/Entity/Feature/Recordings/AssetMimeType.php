<?php

namespace App\Entity\Feature\Recordings;

enum AssetMimeType: string
{
    case ImageWebp = 'image/webp';
    case ImageGif = 'image/gif';
    case VideoWebm = 'video/webm';
    case VideoMp4 = 'video/mp4';
}
