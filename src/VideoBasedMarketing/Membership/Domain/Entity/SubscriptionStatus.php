<?php

namespace App\VideoBasedMarketing\Membership\Domain\Entity;

enum SubscriptionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Ended = 'ended';
}
