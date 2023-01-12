<?php

namespace App\VideoBasedMarketing\Mailings\Infrastructure\Message;

use App\Shared\Infrastructure\Message\AsyncMessageInterface;
use App\VideoBasedMarketing\Mailings\Domain\Entity\VideoMailing;
use InvalidArgumentException;


class ImproveVideoMailingBodyAboveVideoCommandMessage
    implements AsyncMessageInterface
{
    private string $videoMailingId;

    public function __construct(VideoMailing $videoMailing)
    {
        if (is_null($videoMailing->getId())) {
            throw new InvalidArgumentException('video needs an id.');
        }
        $this->videoMailingId = $videoMailing->getId();
    }

    public function getVideoMailingId(): string
    {
        return $this->videoMailingId;
    }
}
