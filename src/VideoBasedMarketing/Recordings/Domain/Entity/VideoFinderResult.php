<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Entity;

readonly class VideoFinderResult
{
    public function __construct(
        public Video $video
    )
    {
    }
}
