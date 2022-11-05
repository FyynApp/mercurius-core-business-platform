<?php

namespace App\VideoBasedMarketing\Membership\Domain\Enum;

enum PaymentProcessor: string
{
    case Stripe = 'stripe';
    case Billwerk = 'billwerk';
}
