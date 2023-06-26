<?php

namespace App\VideoBasedMarketing\Membership\Infrastructure\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Membership\Domain\Entity\MembershipPlan;
use App\VideoBasedMarketing\Membership\Domain\Entity\Package;
use App\VideoBasedMarketing\Membership\Domain\Entity\Purchase;
use App\VideoBasedMarketing\Membership\Domain\Entity\Subscription;
use App\VideoBasedMarketing\Membership\Domain\Enum\MembershipPlanName;
use App\VideoBasedMarketing\Membership\Domain\Enum\PackageName;
use App\VideoBasedMarketing\Membership\Domain\Enum\PaymentCycle;
use App\VideoBasedMarketing\Membership\Domain\Enum\PurchaseStatus;
use App\VideoBasedMarketing\Membership\Domain\Enum\SubscriptionStatus;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipPlanService;
use App\VideoBasedMarketing\Membership\Domain\Service\PackageService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use ValueError;


readonly class PaymentProcessorStripeService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RouterInterface        $router,
        private MembershipPlanService  $membershipPlanService,
        private PackageService         $packageService
    )
    {
    }

    /**
     * @throws ApiErrorException
     * @throws Exception
     */
    public function getSubscriptionCheckoutUrl(
        User           $user,
        MembershipPlan $membershipPlan,
        PaymentCycle   $paymentCycle
    ): string
    {
        $subscription = new Subscription(
            $user->getCurrentlyActiveOrganization()->getOwningUser(),
            $membershipPlan->getName(),
            SubscriptionStatus::Pending
        );

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        Stripe::setApiKey($_ENV['STRIPE_API_KEY']);

        $checkoutSession = Session::create(
            [
                'metadata' => [
                    'purchasing_user_id' =>
                        $user->getId(),
                    'organization_id' =>
                        $user->getCurrentlyActiveOrganization()->getId(),
                    'organization_owning_user_id' =>
                        $user->getCurrentlyActiveOrganization()->getOwningUser()->getId(),
                    'membership_plan_name' =>
                        $membershipPlan->getName()->value
                ],

                'allow_promotion_codes' => true,

                'line_items' => [[
                    'price' => match ($paymentCycle) {
                        PaymentCycle::Monthly => match ($membershipPlan->getName()) {
                            MembershipPlanName::Testdrive => $_ENV['STRIPE_PRICE_ID_MEMBERSHIP_PLAN_TESTDRIVE_MONTHLY'],
                            MembershipPlanName::Independent => $_ENV['STRIPE_PRICE_ID_MEMBERSHIP_PLAN_INDEPENDENT_MONTHLY'],
                            MembershipPlanName::Professional => $_ENV['STRIPE_PRICE_ID_MEMBERSHIP_PLAN_PROFESSIONAL_MONTHLY'],
                            MembershipPlanName::Ultimate => $_ENV['STRIPE_PRICE_ID_MEMBERSHIP_PLAN_ULTIMATE_MONTHLY'],

                            MembershipPlanName::Basic
                            => throw new InvalidArgumentException("Cannot subscribe to plan '{$membershipPlan->getName()->value}'."),

                            default => throw new InvalidArgumentException("Cannot handle plan '{$membershipPlan->getName()->value}'.")
                        },
                        PaymentCycle::Yearly => match ($membershipPlan->getName()) {
                            MembershipPlanName::Testdrive => $_ENV['STRIPE_PRICE_ID_MEMBERSHIP_PLAN_TESTDRIVE_YEARLY'],
                            MembershipPlanName::Independent => $_ENV['STRIPE_PRICE_ID_MEMBERSHIP_PLAN_INDEPENDENT_YEARLY'],
                            MembershipPlanName::Professional => $_ENV['STRIPE_PRICE_ID_MEMBERSHIP_PLAN_PROFESSIONAL_YEARLY'],
                            MembershipPlanName::Ultimate => $_ENV['STRIPE_PRICE_ID_MEMBERSHIP_PLAN_ULTIMATE_YEARLY'],

                            MembershipPlanName::Basic
                            => throw new InvalidArgumentException("Cannot subscribe to plan '{$membershipPlan->getName()->value}'."),

                            default => throw new InvalidArgumentException("Cannot handle plan '{$membershipPlan->getName()->value}'.")
                        },
                    },
                    'quantity' => 1,
                ]],

                'mode' => 'subscription',

                'success_url' => $this->router->generate(
                    'videobasedmarketing.membership.infrastructure.subscription.checkout_with_payment_processor_stripe.success',
                    [
                        'subscriptionId' => $subscription->getId(),
                        'subscriptionHash' => $this->getSubscriptionHash($subscription)
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),

                'cancel_url' => $this->router->generate(
                    'videobasedmarketing.membership.infrastructure.subscription.checkout_with_payment_processor_stripe.cancellation',
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
            ->membershipPlanService
            ->handleSubscriptionCheckoutSuccess($subscription);
    }

    public function getSubscriptionHash(Subscription $subscription): string
    {
        return sha1("hfu537/%69348=894;9 {$subscription->getId()}");
    }



    /**
     * @throws ApiErrorException
     * @throws Exception
     */
    public function getPurchaseCheckoutUrl(
        User    $user,
        Package $package
    ): string
    {
        $purchase = new Purchase(
            $user->getCurrentlyActiveOrganization()->getOwningUser(),
            $package->getName(),
            PurchaseStatus::Pending
        );

        $this->entityManager->persist($purchase);
        $this->entityManager->flush();

        Stripe::setApiKey($_ENV['STRIPE_API_KEY']);

        $checkoutSession = Session::create(
            [
                'metadata' => [
                    'purchasing_user_id' =>
                        $user->getId(),
                    'organization_id' =>
                        $user->getCurrentlyActiveOrganization()->getId(),
                    'organization_owning_user_id' =>
                        $user->getCurrentlyActiveOrganization()->getOwningUser()->getId(),
                    'package_name' =>
                        $package->getName()->value
                ],

                'allow_promotion_codes' => true,

                'line_items' => [[
                    'price' => match ($package->getName()) {
                        PackageName::LingoSyncCreditsFor5Minutes => $_ENV['STRIPE_PRICE_ID_PACKAGE_LINGO_SYNC_CREDITS_FOR_5_MINUTES'],
                        PackageName::LingoSyncCreditsFor10Minutes => $_ENV['STRIPE_PRICE_ID_PACKAGE_LINGO_SYNC_CREDITS_FOR_10_MINUTES'],

                        default => throw new ValueError("Cannot handle package '{$package->getName()->value}'.")
                    },
                    'quantity' => 1,
                ]],

                'mode' => 'purchase',

                'success_url' => $this->router->generate(
                    'videobasedmarketing.membership.infrastructure.purchase.checkout_with_payment_processor_stripe.success',
                    [
                        'purchaseId' => $purchase->getId(),
                        'purchaseHash' => $this->getPurchaseHash($purchase)
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),

                'cancel_url' => $this->router->generate(
                    'videobasedmarketing.membership.infrastructure.purchase.checkout_with_payment_processor_stripe.cancellation',
                    ['purchaseId' => $purchase->getId()],
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
    public function handlePurchaseCheckoutSuccess(
        Purchase $purchase,
        string   $purchaseHash
    ): bool
    {
        if ($purchaseHash !== $this->getPurchaseHash($purchase)) {
            return false;
        }

        return $this
            ->packageService
            ->handlePurchaseCheckoutSuccess($purchase);
    }

    public function getPurchaseHash(
        Purchase $purchase
    ): string
    {
        return sha1("kf8934&&37hbzu%43x! {$purchase->getId()}");
    }
}
