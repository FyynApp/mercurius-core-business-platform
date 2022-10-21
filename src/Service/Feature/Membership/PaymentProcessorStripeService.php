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
                        'feature.membership.subscription.checkout.success',
                        [],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                    . '?planName='
                    . urlencode($membershipPlan->getName()->value)
                    . '&successHash='
                    . urlencode($this->getSubscriptionSuccessHash($user, $membershipPlan)),
                'cancel_url' => $this->router->generate(
                    'feature.membership.subscription.checkout.cancel',
                    [],
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
    public function handleSubscriptionCheckoutSuccess(User $user, string $planName, string $successHash): Subscription
    {
        if (is_null(MembershipPlanName::tryFrom($planName))) {
            throw new Exception("Unknown plan name '$planName'.");
        }

        $membershipPlan = $this->membershipService->getMembershipPlanByName(MembershipPlanName::from($planName));

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
        // @TODO: Make this safe against repeated bookings by creating a pending subscription on start and adding its id here
        return sha1("hfu537/%69348=894;9 {$user->getId()} {$membershipPlan->getName()->value}");
    }
}
