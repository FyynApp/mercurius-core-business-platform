<?php

namespace App\VideoBasedMarketing\Membership\Domain\Entity;

enum PaymentProcessor: string
{
    case Stripe = 'stripe';
    case Billwerk = 'billwerk';
}
