<?php

namespace App\Shared\Infrastructure\Entity;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;

class VerifyAndGetUserAndEntityResult
{
    private User $user;

    private UserOwnedEntityInterface $entity;

    public function __construct(
        User $user,
        UserOwnedEntityInterface $entity
    )
    {
        $this->user = $user;
        $this->entity = $entity;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getEntity(): UserOwnedEntityInterface
    {
        return $this->entity;
    }
}
