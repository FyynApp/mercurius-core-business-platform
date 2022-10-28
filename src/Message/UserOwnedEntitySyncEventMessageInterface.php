<?php

namespace App\Message;

use App\Entity\UserOwnedEntityInterface;


interface UserOwnedEntitySyncEventMessageInterface extends SyncMessageInterface {
    public function getEntity(): UserOwnedEntityInterface;
}
