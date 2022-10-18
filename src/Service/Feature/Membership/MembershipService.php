<?php

namespace App\Service\Feature\Membership;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Membership\MembershipPlan;
use App\Entity\Feature\Membership\MembershipPlanName;
use App\Entity\Feature\Membership\Subscription;
use App\Entity\Feature\Membership\SubscriptionStatus;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Stripe\Checkout\Session;
use Stripe\Stripe;


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
                    9.99,
                    'price_1LuAWFKfVD7HZWQX0Crxe0WY'
                ),

            MembershipPlanName::Pro =>
                new MembershipPlan(
                    $name,
                    19.99,
                    'price_1LuAWaKfVD7HZWQX9iHpiwda'
                ),
        };
    }

    public function getSubscriptionCheckoutUrl(
        User $user,
        MembershipPlan $membershipPlan,
        string $successUrl,
        string $cancelUrl
    ): string
    {
        Stripe::setApiKey('sk_test_T7k8gX5WJjJNGYYRSsmck4wR');

        $checkoutSession = Session::create(
            [
                'metadata' => [
                    'user_id' => $user->getId(),
                    'membership_plan_name' => $membershipPlan->getName()->value
                ],
                'line_items' => [[
                    'price' => $membershipPlan->getStripePriceId(),
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => $successUrl
                    . '?planName='
                    . urlencode($membershipPlan->getName()->value)
                    . '&successHash='
                    . urlencode($this->getSubscriptionSuccessHash($user, $membershipPlan)),
                'cancel_url' => $cancelUrl,
                'automatic_tax' => [
                    'enabled' => true,
                ]
            ]
        );

        return $checkoutSession->url;
    }

    /**
     * @throws Exception
     */
    public function handleSubscriptionCheckoutSuccess(User $user, string $planName, string $successHash): Subscription
    {
        if (is_null(MembershipPlanName::tryFrom($planName))) {
            throw new Exception("Unknown plan name '$planName'.");
        }

        $membershipPlan = $this->getMembershipPlanByName(MembershipPlanName::from($planName));

        if ($successHash !== $this->getSubscriptionSuccessHash($user, $membershipPlan)) {
            throw new Exception("Invalid hash.");
        }

        $subscription = new Subscription($user, $membershipPlan, SubscriptionStatus::Active);
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        return $subscription;
    }

    public function getSubscriptionSuccessHash(User $user, MembershipPlan $membershipPlan): string
    {
        // @TODO: Make this safe against repeated bookings
        return sha1("hfu537/%69348=894;9 {$user->getId()} {$membershipPlan->getName()->value}");
    }
}
