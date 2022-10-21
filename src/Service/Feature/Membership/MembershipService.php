<?php

namespace App\Service\Feature\Membership;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Membership\MembershipPlan;
use App\Entity\Feature\Membership\MembershipPlanName;
use App\Entity\Feature\Membership\PaymentProcessor;
use App\Entity\Feature\Membership\SubscriptionStatus;


class MembershipService
{
    public function getPaymentProcessorForUser(User $user): PaymentProcessor
    {
        return PaymentProcessor::Stripe;
    }

    public function getCurrentlySubscribedMembershipPlanForUser(User $user): MembershipPlan
    {
        foreach ($user->getSubscriptions() as $subscription) {
            if ($subscription->getStatus() === SubscriptionStatus::Active) {
                return $this->getMembershipPlanByName($subscription->getMembershipPlanName());
            }
        }

        return $this->getMembershipPlanByName(MembershipPlanName::Basic);
    }

    public function isPlanBookableForUser(
        MembershipPlan $membershipPlan,
        User $user
    ): bool
    {
        if (!in_array($membershipPlan, $this->getAvailablePlansForUser($user))) {
            return false;
        }

        if ($this->getCurrentlySubscribedMembershipPlanForUser($user) === $membershipPlan) {
            return false;
        }

        return true;
    }

    public function userIsSubscribed(User $user): bool
    {
        foreach ($user->getSubscriptions() as $subscription) {
            if ($subscription->getStatus() === SubscriptionStatus::Active) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return MembershipPlan[]
     */
    public function getAvailablePlansForUser(User $user): array
    {
        $plans = [];
        foreach (MembershipPlanName::cases() as $name) {
            $plans[] = $this->getMembershipPlanByName($name);
        }
        return $plans;
    }

    public function getMembershipPlanByName(MembershipPlanName $name): MembershipPlan
    {
        return match ($name) {
            MembershipPlanName::Basic =>
                new MembershipPlan(
                    $name,
                    0.0
                ),

            MembershipPlanName::Plus =>
                new MembershipPlan(
                    $name,
                    9.99
                ),

            MembershipPlanName::Pro =>
                new MembershipPlan(
                    $name,
                    19.99
                ),
        };
    }
}
