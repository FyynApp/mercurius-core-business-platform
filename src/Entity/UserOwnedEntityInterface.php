<?php

namespace App\Entity;

use App\Entity\Feature\Account\User;


interface UserOwnedEntityInterface
{
    public function getUser(): User;
}
