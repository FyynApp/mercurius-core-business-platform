<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Entity;

readonly class VideoSearchResult
{
    public function __construct(
        public Video $video
    )
    {
    }
}
