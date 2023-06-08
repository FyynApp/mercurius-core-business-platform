<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\SymfonyMessage;

use App\Shared\Infrastructure\SymfonyMessage\AsyncSymfonyMessageInterface;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Entity\HappyScribeTranslationTask;


readonly class CheckHappyScribeTranslationTaskCommandSymfonyMessage
    implements AsyncSymfonyMessageInterface
{
    private string $happyScribeTranslationTaskId;

    public function __construct(
        HappyScribeTranslationTask $happyScribeTranslationTask
    )
    {
        $this->happyScribeTranslationTaskId = $happyScribeTranslationTask->getId();
    }

    public function getHappyScribeTranslationTaskId(): string
    {
        return $this->happyScribeTranslationTaskId;
    }
}
