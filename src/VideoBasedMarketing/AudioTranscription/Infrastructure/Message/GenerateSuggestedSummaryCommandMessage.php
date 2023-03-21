<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\Message;

use App\Shared\Infrastructure\Message\AsyncMessageInterface;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscriptionWebVtt;


readonly class GenerateSuggestedSummaryCommandMessage
    implements AsyncMessageInterface
{
    private string $webVttId;

    public function __construct(
        AudioTranscriptionWebVtt $webVtt
    )
    {
        $this->webVttId = $webVtt->getId();
    }

    public function getWebVttId(): string
    {
        return $this->webVttId;
    }
}
