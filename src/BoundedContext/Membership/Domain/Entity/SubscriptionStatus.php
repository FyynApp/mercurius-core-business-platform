<?php

namespace App\BoundedContext\Membership\Domain\Entity;

enum SubscriptionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Ended = 'ended';
}
