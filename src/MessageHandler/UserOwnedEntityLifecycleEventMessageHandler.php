<?php

namespace App\MessageHandler;

use App\Message\UserOwnedEntityLifecycleEventMessageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;


#[AsMessageHandler]
class UserOwnedEntityLifecycleEventMessageHandler
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger
    )
    {
        $this->logger = $logger;
    }

    public function __invoke(
        UserOwnedEntityLifecycleEventMessageInterface $message
    ): void
    {
        $this->logger->debug(
            "This is the UserOwnedEntitySyncEventMessageHandler
             with entity '{$message->getEntity()->getId()}'
             owned by '{$message->getEntity()->getUser()->getUserIdentifier()}'."
        );

        // TODO: write application event to DWH
    }
}
