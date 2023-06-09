<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\SymfonyMessageHandler;

use App\VideoBasedMarketing\LingoSync\Domain\Entity\LingoSyncProcessTask;
use App\VideoBasedMarketing\LingoSync\Domain\Service\LingoSyncDomainService;
use App\VideoBasedMarketing\LingoSync\Domain\SymfonyMessage\HandleTaskCommandSymfonyMessage;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;


#[AsMessageHandler]
readonly class HandleTaskCommandSymfonyMessageHandler
{
    public function __construct(
        private LingoSyncDomainService $lingoSyncDomainService,
        private EntityManagerInterface $entityManager
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

        $this->lingoSyncDomainService->handleTask($task);
    }
}
