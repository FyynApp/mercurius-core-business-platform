<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\SymfonyMessage;

use App\Shared\Infrastructure\SymfonyMessage\AsyncSymfonyMessageInterface;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscription;


readonly class CreateHappyScribeTranscriptionCommandSymfonyMessage
    implements AsyncSymfonyMessageInterface
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
