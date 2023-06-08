<?php

namespace App\VideoBasedMarketing\AudioTranscription\Domain\SymfonyEvent;

use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscriptionWebVtt;


readonly class WebVttBecameAvailableSymfonyEvent
{
    public function __construct(
        public AudioTranscriptionWebVtt $webVtt
    )
    {
    }
}
