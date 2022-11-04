<?php

namespace App\Message;

use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;


interface UserOwnedEntityLifecycleEventMessageInterface
    extends SyncMessageInterface
{
    public function getEntity(): UserOwnedEntityInterface;
}
