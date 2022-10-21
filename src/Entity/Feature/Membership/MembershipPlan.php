<?php

namespace App\Entity\Feature\Membership;

class MembershipPlan
{
    private MembershipPlanName $name;

    private float $pricePerMonth;


    public function __construct(
        MembershipPlanName $name,
        float $pricePerMonth,
    )
    {
        $this->name = $name;
        $this->pricePerMonth = $pricePerMonth;
    }

    public function getName(): MembershipPlanName
    {
        return $this->name;
    }

    public function getPricePerMonth(): float
    {
        return $this->pricePerMonth;
    }
}
