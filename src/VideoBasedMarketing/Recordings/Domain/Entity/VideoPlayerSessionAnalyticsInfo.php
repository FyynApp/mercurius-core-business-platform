<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Entity;

use ValueError;


readonly class VideoPlayerSessionAnalyticsInfo
{
    public function __construct(
        public VideoPlayerSession $videoPlayerSession,
        public array              $secondsToDidWatch
    )
    {
        $previousSecond = -1;
        foreach ($this->secondsToDidWatch as $second => $didWatch) {
            if (gettype($didWatch) !== 'boolean') {
                throw new ValueError();
            }

            if (gettype($second) !== 'integer') {
                throw new ValueError();
            }

            if ($second -1 !== $previousSecond) {
                throw new ValueError();
            }

            $previousSecond = $second;
        }
    }
}
