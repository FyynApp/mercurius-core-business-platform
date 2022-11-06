<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\Message;

use App\Shared\Infrastructure\Message\AsyncMessageInterface;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use InvalidArgumentException;


class GenerateMissingAssetsCommandMessage
    implements AsyncMessageInterface
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
