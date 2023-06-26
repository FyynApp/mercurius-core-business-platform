<?php

namespace App\VideoBasedMarketing\Membership\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Membership\Domain\Entity\Package;
use App\VideoBasedMarketing\Membership\Domain\Entity\Purchase;
use App\VideoBasedMarketing\Membership\Domain\Enum\PackageName;
use App\VideoBasedMarketing\Membership\Domain\Enum\PaymentProcessor;
use App\VideoBasedMarketing\Membership\Domain\Enum\PurchaseStatus;
use App\VideoBasedMarketing\Organization\Domain\Service\OrganizationDomainService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use ValueError;


readonly class PackageService
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

    public function getPackageByName(
        PackageName $name
    ): Package
    {
        return match ($name) {
            PackageName::FreeLingoSyncCreditsFor10Minutes =>
            new Package(
                $name,
                0.0
            ),

            PackageName::LingoSyncCreditsFor5Minutes =>
            new Package(
                $name,
                5 * 0.80
            ),

            PackageName::LingoSyncCreditsFor10Minutes =>
            new Package(
                $name,
                10 * 0.80
            ),
        };
    }

    /**
     * @throws Exception
     */
    public function handlePurchaseCheckoutSuccess(
        Purchase $purchase
    ): bool
    {
        $purchase->setStatus(PurchaseStatus::Active);
        $this->entityManager->persist($purchase);
        $this->entityManager->flush();

        return true;
    }
}
