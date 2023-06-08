<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\SymfonyMessage;

use App\Shared\Infrastructure\SymfonyMessage\AsyncSymfonyMessageInterface;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscriptionWebVtt;


readonly class GenerateSuggestedSummaryCommandSymfonyMessage
    implements AsyncSymfonyMessageInterface
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
