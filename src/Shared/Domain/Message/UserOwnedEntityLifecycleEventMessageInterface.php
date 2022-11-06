<?php

namespace App\Shared\Domain\Message;

use App\Shared\Infrastructure\Message\SyncMessageInterface;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;


interface UserOwnedEntityLifecycleEventMessageInterface
    extends SyncMessageInterface
{
    public function getEntity(): UserOwnedEntityInterface;
}
