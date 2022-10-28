<?php

namespace App\MessageHandler;

use App\Message\UserOwnedEntitySyncEventMessageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;


#[AsMessageHandler]
class UserOwnedEntitySyncEventMessageHandler
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger
    )
    {
        $this->logger = $logger;
    }

    public function __invoke(
        UserOwnedEntitySyncEventMessageInterface $message
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
