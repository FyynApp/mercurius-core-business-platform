<?php

namespace App\VideoBasedMarketing\Membership\Domain\Entity;


use App\VideoBasedMarketing\Membership\Domain\Enum\MembershipPlanName;


class MembershipPlan
{
    private MembershipPlanName $name;

    private bool $mustBeBought;

    private float $pricePerMonth;


    public function __construct(
        MembershipPlanName $name,
        bool $mustBeBought,
        float $pricePerMonth = 0.0,
    )
    {
        $this->name = $name;
        $this->mustBeBought = $mustBeBought;
        $this->pricePerMonth = $pricePerMonth;
    }

    public function getName(): MembershipPlanName
    {
        return $this->name;
    }

    public function mustBeBought(): bool
    {
        return $this->mustBeBought;
    }

    public function getPricePerMonth(): float
    {
        return $this->pricePerMonth;
    }
}
