<?php

namespace App\Shared\Domain\SymfonyMessageHandler;

use App\Shared\Domain\SymfonyMessage\UserOwnedEntityLifecycleEventSymfonyMessageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;


#[AsMessageHandler]
class UserOwnedEntityLifecycleEventSymfonyMessageHandler
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger
    )
    {
        $this->logger = $logger;
    }

    public function __invoke(
        UserOwnedEntityLifecycleEventSymfonyMessageInterface $message
    ): void
    {
        $this->logger->debug(
            "This is the UserOwnedEntityLifecycleEventSymfonyMessageHandler
             with entity '{$message->getEntity()->getId()}'
             of class '" . $message->getEntity()::class . "'
             owned by user '{$message->getEntity()->getUser()->getUserIdentifier()}'."
        );

        // TODO: write application event to DWH
    }
}
