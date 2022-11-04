<?php

namespace App\VideoBasedMarketing\Membership\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Membership\Domain\Entity\MembershipPlan;
use App\VideoBasedMarketing\Membership\Domain\Entity\MembershipPlanName;
use App\VideoBasedMarketing\Membership\Domain\Entity\PaymentProcessor;
use App\VideoBasedMarketing\Membership\Domain\Entity\Subscription;
use App\VideoBasedMarketing\Membership\Domain\Entity\SubscriptionStatus;
use Doctrine\ORM\EntityManagerInterface;
use Exception;


class MembershipService
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    )
    {
        $this->entityManager = $entityManager;
    }

    public function getPaymentProcessorForUser(
        User $user
    ): PaymentProcessor
    {
        return PaymentProcessor::Stripe;
    }

    public function getCurrentlySubscribedMembershipPlanForUser(
        User $user
    ): MembershipPlan
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
        User           $user
    ): bool
    {
        if (!$membershipPlan->mustBeBought()) {
            return false;
        }

        if (!in_array($membershipPlan, $this->getAvailablePlansForUser($user))) {
            return false;
        }

        if ($this->getCurrentlySubscribedMembershipPlanForUser($user) === $membershipPlan) {
            return false;
        }

        return true;
    }

    public function userIsSubscribed(
        User $user
    ): bool
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
    public function getAvailablePlansForUser(
        User $user
    ): array
    {
        $plans = [];
        foreach (MembershipPlanName::cases() as $name) {
            $plans[] = $this->getMembershipPlanByName($name);
        }
        return $plans;
    }

    public function getMembershipPlanByName(
        MembershipPlanName $name
    ): MembershipPlan
    {
        return match ($name) {
            MembershipPlanName::Basic =>
                new MembershipPlan(
                    $name,
                    false
                ),

            MembershipPlanName::Plus =>
                new MembershipPlan(
                    $name,
                    true,
                    9.99
                ),

            MembershipPlanName::Pro =>
                new MembershipPlan(
                    $name,
                    true,
                    19.99
                ),
        };
    }

    /**
     * @throws Exception
     */
    public function handleSubscriptionCheckoutSuccess(
        Subscription $subscription
    ): bool
    {
        $subscription->setStatus(SubscriptionStatus::Active);
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        return true;
    }
}
