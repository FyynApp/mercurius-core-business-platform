<?php

namespace App\Entity\Feature\Membership;

enum PaymentProcessor: string
{
    case Stripe = 'stripe';
    case Billwerk = 'billwerk';
}
