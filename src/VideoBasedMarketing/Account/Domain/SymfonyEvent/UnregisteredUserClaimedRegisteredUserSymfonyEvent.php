<?php

namespace App\VideoBasedMarketing\Account\Domain\SymfonyEvent;

use App\VideoBasedMarketing\Account\Domain\Entity\User;

readonly class UnregisteredUserClaimedRegisteredUserSymfonyEvent
{
    public function __construct(
        public User $unregisteredUser,
        public User $registeredUser
    )
    {
    }
}
