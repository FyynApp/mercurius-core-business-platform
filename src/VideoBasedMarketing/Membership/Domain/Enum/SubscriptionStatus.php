<?php

namespace App\VideoBasedMarketing\Membership\Domain\Enum;

enum SubscriptionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Ended = 'ended';
}
