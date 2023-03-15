<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\Message;

use App\Shared\Infrastructure\Message\AsyncMessageInterface;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Entity\HappyScribeTranslationTask;


readonly class CheckHappyScribeTranslationTaskCommandMessage
    implements AsyncMessageInterface
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
