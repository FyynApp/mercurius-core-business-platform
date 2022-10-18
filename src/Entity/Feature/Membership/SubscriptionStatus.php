<?php

namespace App\Entity\Feature\Membership;

enum SubscriptionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Ended = 'ended';
}
