<?php

namespace App\VideoBasedMarketing\Membership\Domain\SymfonyEvent;

use App\VideoBasedMarketing\Membership\Domain\Entity\Purchase;


readonly class PackageWasPurchasedSymfonyEvent
{
    public function __construct(
        public Purchase $purchase
    )
    {
    }
}
