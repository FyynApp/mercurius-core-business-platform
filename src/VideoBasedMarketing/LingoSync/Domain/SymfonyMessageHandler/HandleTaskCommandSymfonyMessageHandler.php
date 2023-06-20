<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\SymfonyMessageHandler;

use App\VideoBasedMarketing\LingoSync\Domain\Entity\LingoSyncProcessTask;
use App\VideoBasedMarketing\LingoSync\Domain\Enum\LingoSyncProcessTaskStatus;
use App\VideoBasedMarketing\LingoSync\Domain\Service\LingoSyncDomainService;
use App\VideoBasedMarketing\LingoSync\Domain\SymfonyMessage\HandleTaskCommandSymfonyMessage;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Throwable;


#[AsMessageHandler]
readonly class HandleTaskCommandSymfonyMessageHandler
{
    public function __construct(
        private LingoSyncDomainService $lingoSyncDomainService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface        $logger
    )
    {
    }

    /**
     * @throws Exception
     */
    public function __invoke(
        HandleTaskCommandSymfonyMessage $message
    ): void
    {
        $task = $this->entityManager->find(
            LingoSyncProcessTask::class,
            $message->getTaskId()
        );

        if (is_null($task)) {
            throw new UnrecoverableMessageHandlingException(
                "Could not find task with id '{$message->getTaskId()}'."
            );
        }

        try {
            $this->lingoSyncDomainService->handleTask($task);
        } catch (Throwable $t) {
            $task->setStatus(LingoSyncProcessTaskStatus::Errored);
            $task->setResult($t->getMessage());
            $this->entityManager->persist($task);
            $this->entityManager->flush();

            $this->logger->error(
                "Task with id '{$task->getId()}' failed. Message: '{$t->getMessage()}'."
            );

            $this->logger->error(
                "Task with id '{$task->getId()}' failed. File: '{$t->getFile()}', line: {$t->getLine()}."
            );

            $this->logger->error(
                "Task with id '{$task->getId()}' failed. Trace: '{$t->getTraceAsString()}'."
            );

            throw new UnrecoverableMessageHandlingException(
                "Task with id '{$task->getId()}' failed: '{$t->getMessage()}'."
            );
        }
    }
}
