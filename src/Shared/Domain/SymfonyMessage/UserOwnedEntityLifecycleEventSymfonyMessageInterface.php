<?php

namespace App\Shared\Domain\SymfonyMessage;

use App\Shared\Infrastructure\SymfonyMessage\SyncSymfonyMessageInterface;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;


interface UserOwnedEntityLifecycleEventSymfonyMessageInterface
    extends SyncSymfonyMessageInterface
{
    public function getEntity(): UserOwnedEntityInterface;
}
