<?php

namespace App\VideoBasedMarketing\Membership\Domain\Enum;

enum PaymentCycle: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';
}
