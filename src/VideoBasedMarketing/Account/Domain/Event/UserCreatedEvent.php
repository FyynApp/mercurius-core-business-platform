<?php

namespace App\VideoBasedMarketing\Account\Domain\Event;

use App\VideoBasedMarketing\Account\Domain\Entity\User;

readonly class UserCreatedEvent
{
    public function __construct(
        public User $user
    )
    {
    }
}
