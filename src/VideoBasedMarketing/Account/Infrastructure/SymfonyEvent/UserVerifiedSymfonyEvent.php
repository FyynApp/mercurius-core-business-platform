<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\SymfonyEvent;

use App\VideoBasedMarketing\Account\Domain\Entity\User;

class UserVerifiedSymfonyEvent
{
    private User $user;

    public function __construct(
        User $user
    )
    {
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
