<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\SymfonyMessage;

use App\Shared\Infrastructure\SymfonyMessage\AsyncSymfonyMessageInterface;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Entity\HappyScribeTranscription;


readonly class CheckHappyScribeTranscriptionCommandSymfonyMessage
    implements AsyncSymfonyMessageInterface
{
    private string $happyScribeTranscriptionId;

    public function __construct(
        HappyScribeTranscription $happyScribeTranscription
    )
    {
        $this->happyScribeTranscriptionId = $happyScribeTranscription->getId();
    }

    public function getHappyScribeTranscriptionId(): string
    {
        return $this->happyScribeTranscriptionId;
    }
}
