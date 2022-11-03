<?php

namespace App\BoundedContext\Membership\Domain\Entity;

enum PaymentProcessor: string
{
    case Stripe = 'stripe';
    case Billwerk = 'billwerk';
}
