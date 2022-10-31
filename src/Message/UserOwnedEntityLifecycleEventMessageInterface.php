<?php

namespace App\Message;

use App\Entity\UserOwnedEntityInterface;


interface UserOwnedEntityLifecycleEventMessageInterface extends SyncMessageInterface {
    public function getEntity(): UserOwnedEntityInterface;
}
