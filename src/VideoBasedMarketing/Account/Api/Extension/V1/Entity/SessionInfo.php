<?php

namespace App\VideoBasedMarketing\Account\Api\Extension\V1\Entity;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Membership\Domain\Entity\MembershipPlan;


class SessionInfo
{
    private User $user;

    private MembershipPlan $membershipPlan;

    public function __construct(
        User           $user,
        MembershipPlan $membershipPlan
    )
    {
        $this->user = $user;
        $this->membershipPlan = $membershipPlan;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getMembershipPlan(): MembershipPlan
    {
        return $this->membershipPlan;
    }
}
