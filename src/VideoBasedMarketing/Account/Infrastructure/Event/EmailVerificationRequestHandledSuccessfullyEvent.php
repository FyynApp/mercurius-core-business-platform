<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\Event;

use App\VideoBasedMarketing\Account\Domain\Entity\User;

class EmailVerificationRequestHandledSuccessfullyEvent
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
