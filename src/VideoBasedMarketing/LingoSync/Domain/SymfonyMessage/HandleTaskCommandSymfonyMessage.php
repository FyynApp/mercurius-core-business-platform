<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\SymfonyMessage;

use App\Shared\Infrastructure\SymfonyMessage\AsyncSymfonyMessageInterface;
use App\VideoBasedMarketing\LingoSync\Domain\Entity\LingoSyncProcessTask;

readonly class HandleTaskCommandSymfonyMessage
    implements AsyncSymfonyMessageInterface
{
    private string $taskId;

    public function __construct(
        LingoSyncProcessTask $task
    )
    {
        $this->taskId = $task->getId();
    }

    public function getTaskId(): string
    {
        return $this->taskId;
    }
}
