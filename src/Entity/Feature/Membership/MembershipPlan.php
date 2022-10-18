<?php

namespace App\Entity\Feature\Membership;

class MembershipPlan
{
    private MembershipPlanName $name;

    private float $pricePerMonth;

    private ?string $stripePriceId;


    public function __construct(
        MembershipPlanName $name,
        float $pricePerMonth,
        ?string $stripePriceId = null
    )
    {
        $this->name = $name;
        $this->pricePerMonth = $pricePerMonth;
        $this->stripePriceId = $stripePriceId;
    }

    public function getName(): MembershipPlanName
    {
        return $this->name;
    }

    public function getPricePerMonth(): float
    {
        return $this->pricePerMonth;
    }

    public function getStripePriceId(): ?string
    {
        return $this->stripePriceId;
    }

    public function isBookable(): bool
    {
        return !is_null($this->getStripePriceId());
    }
}
