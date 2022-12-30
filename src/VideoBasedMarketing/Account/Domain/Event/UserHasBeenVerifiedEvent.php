<?php

namespace App\VideoBasedMarketing\Account\Domain\Event;

use App\VideoBasedMarketing\Account\Domain\Entity\User;

readonly class UserHasBeenVerifiedEvent
{
    public function __construct(
        public User $user
    )
    {}
}
