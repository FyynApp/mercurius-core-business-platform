<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\Message;

use App\Shared\Infrastructure\Message\AsyncMessageInterface;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscription;


readonly class CreateHappyScribeTranscriptionCommandMessage
    implements AsyncMessageInterface
{
    private string $audioTranscriptionId;

    public function __construct(
        AudioTranscription $audioTranscription
    )
    {
        $this->audioTranscriptionId = $audioTranscription->getId();
    }

    public function getAudioTranscriptionId(): string
    {
        return $this->audioTranscriptionId;
    }
}
