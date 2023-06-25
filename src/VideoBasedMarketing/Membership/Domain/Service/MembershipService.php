<?php

namespace App\VideoBasedMarketing\Membership\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Membership\Domain\Entity\MembershipPlan;
use App\VideoBasedMarketing\Membership\Domain\Entity\Subscription;
use App\VideoBasedMarketing\Membership\Domain\Enum\Capability;
use App\VideoBasedMarketing\Membership\Domain\Enum\MembershipPlanName;
use App\VideoBasedMarketing\Membership\Domain\Enum\PaymentProcessor;
use App\VideoBasedMarketing\Membership\Domain\Enum\SubscriptionStatus;
use App\VideoBasedMarketing\Organization\Domain\Entity\OrganizationOwnedEntityInterface;
use App\VideoBasedMarketing\Organization\Domain\Service\OrganizationDomainService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use ValueError;


readonly class MembershipService
{
    public function __construct(
        private EntityManagerInterface    $entityManager,
        private OrganizationDomainService $organizationDomainService
    )
    {
    }

    public function getPaymentProcessorForUser(
        User $user
    ): PaymentProcessor
    {
        return PaymentProcessor::Stripe;
    }

    public function getSubscribedMembershipPlanForCurrentlyActiveOrganization(
        User $user
    ): MembershipPlan
    {
        $orgOwningUser = $this
            ->organizationDomainService
            ->getCurrentlyActiveOrganizationOfUser($user)
            ->getOwningUser()
        ;

        foreach ($orgOwningUser->getSubscriptions() as $subscription) {
            if ($subscription->getStatus() === SubscriptionStatus::Active) {
                return $this->getMembershipPlanByName($subscription->getMembershipPlanName());
            }
        }

        return $this->getMembershipPlanByName(MembershipPlanName::Basic);
    }

    public function subscriptionOfOrganizationOwnedEntityHasCapability(
        OrganizationOwnedEntityInterface $organizationOwnedEntity,
        Capability                       $capability
    ): bool
    {
        $orgOwningUser = $organizationOwnedEntity
            ->getOrganization()
            ->getOwningUser()
        ;

        foreach ($orgOwningUser->getSubscriptions() as $subscription) {
            if ($subscription->getStatus() === SubscriptionStatus::Active) {
                if ($this
                    ->getMembershipPlanByName($subscription->getMembershipPlanName())
                    ->hasCapability($capability)
                ) {
                    return true;
                }
            }
        }

        return false;
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

        if ($this->getSubscribedMembershipPlanForCurrentlyActiveOrganization($user)->getName()
            === $membershipPlan->getName()
        ) {
            return false;
        }

        return true;
    }

    public function userIsSubscribedToPlanThatMustBeBought(
        User $user
    ): bool
    {
        $orgOwningUser = $this
            ->organizationDomainService
            ->getCurrentlyActiveOrganizationOfUser($user)
            ->getOwningUser()
        ;


        foreach ($orgOwningUser->getSubscriptions() as $subscription) {
            if ($subscription->getStatus() === SubscriptionStatus::Active) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return MembershipPlan[]
     */
    public function getAvailablePlans(): array
    {
        $plans = [];
        foreach (MembershipPlanName::cases() as $name) {
            $plans[] = $this->getMembershipPlanByName($name);
        }
        return $plans;
    }

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
                false,
                0.0,
                0.0,
                [],
                0.0,
                60 * 5,
                100 * 1024 * 1024 // 100 MiB
            ),

            MembershipPlanName::Testdrive =>
            new MembershipPlan(
                $name,
                true,
                4.99,
                48.00,
                [
                    Capability::CustomDomain,
                    Capability::CustomLogoOnLandingpage,
                    Capability::AdFreeLandingpages,
                    Capability::BrandingFreeEmbeddableVideoPlayer,
                    Capability::VideoTranslation,
                ],
                (float)(60 * 5),
                60 * 10,
                256 * 1024 * 1024 // 0.25 GiB
            ),

            MembershipPlanName::Independent =>
            new MembershipPlan(
                $name,
                true,
                40.0,
                324.0,
                [
                    Capability::CustomDomain,
                    Capability::CustomLogoOnLandingpage,
                    Capability::AdFreeLandingpages,
                    Capability::BrandingFreeEmbeddableVideoPlayer,
                    Capability::VideoTranslation,
                ],
                (float)(60 * 60 * 0.5),
                60 * 20,
                512 * 1024 * 1024 // 0.5 GiB
            ),

            MembershipPlanName::Professional =>
            new MembershipPlan(
                $name,
                true,
                70.0,
                564.0,
                [
                    Capability::CustomDomain,
                    Capability::CustomLogoOnLandingpage,
                    Capability::AdFreeLandingpages,
                    Capability::BrandingFreeEmbeddableVideoPlayer,
                    Capability::VideoTranslation,
                ],
                (float)(60 * 60),
                60 * 60,
                1024 * 1024 * 1024 // 1 GiB
            ),

            MembershipPlanName::Ultimate =>
            new MembershipPlan(
                $name,
                true,
                190.0,
                1524.0,
                [
                    Capability::CustomDomain,
                    Capability::CustomLogoOnLandingpage,
                    Capability::AdFreeLandingpages,
                    Capability::BrandingFreeEmbeddableVideoPlayer,
                    Capability::VideoTranslation,
                ],
                (float)(60 * 60 * 3),
                60 * 60 * 2,
                2 * 1024 * 1024 * 1024 // 2 GiB
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

    /** @param MembershipPlan[] $capabilities */
    public function getCheapestMembershipPlanRequiredForCapabilities(
        array $capabilities
    ): ?MembershipPlan
    {
        /** @var Capability $capability */
        foreach ($capabilities as $key => $capability) {
            if (get_class($capability) !== Capability::class) {
                throw new ValueError('$capabilities['. $key .'] has class ' . get_class($capability) . '.');
            }
        }

        $plans = $this->getAvailablePlans();

        foreach ($plans as $plan) {
            $planHasAllCapabilities = true;
            foreach ($capabilities as $capability) {
                if (!$plan->hasCapability($capability)) {
                    $planHasAllCapabilities = false;
                }
            }
            if ($planHasAllCapabilities) {
                return $plan;
            }
        }

        return null;
    }
}
