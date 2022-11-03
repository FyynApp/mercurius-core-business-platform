<?php

namespace App\Message;

use App\BoundedContext\Account\Domain\Entity\UserOwnedEntityInterface;


interface UserOwnedEntityLifecycleEventMessageInterface
    extends SyncMessageInterface
{
    public function getEntity(): UserOwnedEntityInterface;
}
