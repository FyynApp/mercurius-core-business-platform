<?php

namespace App\VideoBasedMarketing\Mailings\Infrastructure\SymfonyMessage;

use App\Shared\Infrastructure\SymfonyMessage\AsyncSymfonyMessageInterface;
use App\VideoBasedMarketing\Mailings\Domain\Entity\VideoMailing;
use InvalidArgumentException;


class ImproveVideoMailingBodyAboveVideoCommandSymfonyMessage
    implements AsyncSymfonyMessageInterface
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
