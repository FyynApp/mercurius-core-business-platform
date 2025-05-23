<?php

namespace App\Shared\Infrastructure\SymfonyMessageHandler;

use App\Shared\Infrastructure\SymfonyMessage\ClearTusCacheCommandSymfonyMessage;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use TusPhp\Tus\Server;

#[AsMessageHandler]
class ClearTusCacheCommandSymfonyMessageHandler
{
    private EntityManagerInterface $entityManager;

    private Server $server;


    public function __construct(
        EntityManagerInterface $entityManager,
        Server                 $server,
    )
    {
        $this->entityManager = $entityManager;
        $this->server = $server;
    }

    /**
     */
    public function __invoke(
        ClearTusCacheCommandSymfonyMessage $message
    ): void
    {
        /** @var null|User $user */
        $user = $this->entityManager->find(
            User::class,
            $message->getUserId()
        );

        if (is_null($user)) {
            throw new UnrecoverableMessageHandlingException(
                "Could not find user with id '{$message->getUserId()}'."
            );
        }

        $this->server->getCache()->setPrefix($user->getId());
        $this->server->getCache()->delete($message->getToken());
    }
}
