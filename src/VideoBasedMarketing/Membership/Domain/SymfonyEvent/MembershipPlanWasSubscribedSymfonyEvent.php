<?php

namespace App\VideoBasedMarketing\Membership\Domain\SymfonyEvent;

use App\VideoBasedMarketing\Membership\Domain\Entity\Subscription;


readonly class MembershipPlanWasSubscribedSymfonyEvent
{
    public function __construct(
        public Subscription $subscription
    )
    {
    }
}
