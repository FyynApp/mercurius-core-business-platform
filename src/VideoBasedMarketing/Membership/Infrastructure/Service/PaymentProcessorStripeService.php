<?php

namespace App\VideoBasedMarketing\Membership\Infrastructure\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Membership\Domain\Entity\MembershipPlan;
use App\VideoBasedMarketing\Membership\Domain\Entity\MembershipPlanName;
use App\VideoBasedMarketing\Membership\Domain\Entity\Subscription;
use App\VideoBasedMarketing\Membership\Domain\Entity\SubscriptionStatus;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipService;
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

    private MembershipService $membershipService;

    public function __construct(
        EntityManagerInterface $entityManager,
        RouterInterface        $router,
        MembershipService      $membershipService
    )
    {
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->membershipService = $membershipService;
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
                    'videobasedmarketing.membership.subscription.checkout_with_payment_processor_stripe.success',
                    [
                        'subscriptionId' => $subscription->getId(),
                        'subscriptionHash' => $this->getSubscriptionHash($subscription)
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),

                'cancel_url' => $this->router->generate(
                    'videobasedmarketing.membership.subscription.checkout_with_payment_processor_stripe.cancellation',
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
        string       $subscriptionHash
    ): bool
    {
        if ($subscriptionHash !== $this->getSubscriptionHash($subscription)) {
            return false;
        }

        return $this
            ->membershipService
            ->handleSubscriptionCheckoutSuccess($subscription);
    }

    public function getSubscriptionHash(Subscription $subscription): string
    {
        return sha1("hfu537/%69348=894;9 {$subscription->getId()}");
    }
}
