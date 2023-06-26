<?php

namespace App\VideoBasedMarketing\Membership\Domain\Enum;

enum PurchaseStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
}
