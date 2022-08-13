<?php

namespace App\Message\Feature\Recordings;

use App\Entity\Feature\Recordings\Video;
use InvalidArgumentException;

class GenerateMissingAssetsCommandMessage
{
    private string $videoId;

    public function __construct(Video $video)
    {
        if (is_null($video->getId())) {
            throw new InvalidArgumentException('recording session needs an id.');
        }
        $this->videoId = $video->getId();
    }

    public function getVideoId(): string
    {
        return $this->videoId;
    }
}
