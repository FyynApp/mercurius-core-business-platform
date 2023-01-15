<?php

namespace App\VideoBasedMarketing\Account\Domain\Event;

use App\VideoBasedMarketing\Account\Domain\Entity\User;

readonly class UnregisteredUserClaimedRegisteredUserEvent
{
    public function __construct(
        public User $unregisteredUser,
        public User $registeredUser
    )
    {
    }
}
