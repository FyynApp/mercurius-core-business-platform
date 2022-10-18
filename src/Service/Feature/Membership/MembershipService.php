<?php

namespace App\Service\Feature\Membership;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Membership\MembershipPlan;
use App\Entity\Feature\Membership\MembershipPlanName;
use App\Entity\Feature\Membership\SubscriptionStatus;
use Doctrine\ORM\EntityManagerInterface;


class MembershipService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getMembershipPlanForUser(User $user): MembershipPlan
    {
        foreach ($user->getSubscriptions() as $subscription) {
            if ($subscription->getStatus() === SubscriptionStatus::Active) {
                return $this->getMembershipPlanByName($subscription->getMembershipPlanName());
            }
        }

        return $this->getMembershipPlanByName(MembershipPlanName::Basic);
    }

    public function getMembershipPlanByName(MembershipPlanName $name): MembershipPlan
    {
        return match ($name) {
            MembershipPlanName::Basic => new MembershipPlan($name, 0.0),

            MembershipPlanName::Plus => new MembershipPlan($name, 9.99),

            MembershipPlanName::Pro => new MembershipPlan($name, 19.99),
        };
    }
}
