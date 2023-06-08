<?php

namespace App\VideoBasedMarketing\Account\Domain\SymfonyEvent;

use App\VideoBasedMarketing\Account\Domain\Entity\User;

readonly class UserCreatedSymfonyEvent
{
    public function __construct(
        public User $user
    )
    {
    }
}
