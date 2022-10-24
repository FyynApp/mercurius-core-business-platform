<?php

namespace App\Service\Feature\Membership;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Membership\MembershipPlan;
use App\Entity\Feature\Membership\MembershipPlanName;
use App\Entity\Feature\Membership\Subscription;
use App\Entity\Feature\Membership\SubscriptionStatus;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;


class PaymentProcessorStripeService
{
    private EntityManagerInterface $entityManager;

    private RouterInterface $router;

    public function __construct(
        EntityManagerInterface $entityManager,
        RouterInterface        $router
    )
    {
        $this->entityManager = $entityManager;
        $this->router = $router;
    }

    public function getSubscriptionCheckoutUrl(
        User           $user,
        MembershipPlan $membershipPlan
    ): string
    {
        $subscription = new Subscription(
            $user,
            $membershipPlan,
            SubscriptionStatus::Pending
        );

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        Stripe::setApiKey('sk_test_T7k8gX5WJjJNGYYRSsmck4wR');

        $checkoutSession = Session::create(
            [
                'metadata' => [
                    'user_id' => $user->getId(),
                    'membership_plan_name' => $membershipPlan->getName()->value
                ],

                'line_items' => [[
                    'price' => match ($membershipPlan->getName()) {
                        MembershipPlanName::Plus => 'price_1LuAWFKfVD7HZWQX0Crxe0WY',
                        MembershipPlanName::Pro => 'price_1LuAWaKfVD7HZWQX9iHpiwda',
                        MembershipPlanName::Basic => throw new InvalidArgumentException("Cannot subscribe to plan '{$membershipPlan->getName()->value}'.")
                    },
                    'quantity' => 1,
                ]],

                'mode' => 'subscription',

                'success_url' => $this->router->generate(
                    'feature.membership.subscription.checkout_with_payment_processor_stripe.success',
                    [
                        'subscriptionId' => $subscription->getId(),
                        'subscriptionHash' => $this->getSubscriptionHash($subscription)
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),

                'cancel_url' => $this->router->generate(
                    'feature.membership.subscription.checkout_with_payment_processor_stripe.cancel',
                    ['subscriptionId' => $subscription->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),

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
    public function handleSubscriptionCheckoutSuccess(
        Subscription $subscription,
        string $subscriptionHash
    ): bool
    {
        if ($subscriptionHash !== $this->getSubscriptionHash($subscription)) {
            return false;
        }

        $subscription->setStatus(SubscriptionStatus::Active);
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        return true;
    }

    public function getSubscriptionHash(Subscription $subscription): string
    {
        return sha1("hfu537/%69348=894;9 {$subscription->getId()}");
    }
}
