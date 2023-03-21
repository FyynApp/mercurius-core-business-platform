<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\Message;

use App\Shared\Infrastructure\Message\AsyncMessageInterface;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Entity\HappyScribeTranscription;


readonly class CheckHappyScribeTranscriptionCommandMessage
    implements AsyncMessageInterface
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
