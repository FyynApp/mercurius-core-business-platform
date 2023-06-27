<?php

namespace App\VideoBasedMarketing\Membership\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Membership\Domain\Entity\Package;
use App\VideoBasedMarketing\Membership\Domain\Entity\Purchase;
use App\VideoBasedMarketing\Membership\Domain\Enum\PackageName;
use App\VideoBasedMarketing\Membership\Domain\Enum\PaymentProcessor;
use App\VideoBasedMarketing\Membership\Domain\Enum\PurchaseStatus;
use App\VideoBasedMarketing\Membership\Domain\SymfonyEvent\PackageWasPurchasedSymfonyEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Exception;


readonly class PackageService
{
    public function __construct(
        private EntityManagerInterface   $entityManager,
        private EventDispatcherInterface $eventDispatcher
    )
    {
    }

    public function getPaymentProcessorForUser(
        User $user
    ): PaymentProcessor
    {
        return PaymentProcessor::Stripe;
    }

    /**
     * @throws Exception
     */
    public function getPackageByName(
        PackageName $name
    ): Package
    {
        return match ($name) {
            PackageName::LingoSyncCreditsFor5Minutes =>
            new Package(
                $name,
                5 * 0.80
            ),

            PackageName::LingoSyncCreditsFor10Minutes =>
            new Package(
                $name,
                10 * 0.70
            ),

            PackageName::LingoSyncCreditsFor30Minutes =>
            new Package(
                $name,
                30 * 0.60
            ),

            PackageName::LingoSyncCreditsFor60Minutes =>
            new Package(
                $name,
                60 * 0.50
            ),

            default => throw new Exception('Unknown package name: ' . $name->value)
        };
    }

    /**
     * @throws Exception
     */
    public function handlePurchaseCheckoutSuccess(
        Purchase $purchase
    ): bool
    {
        $purchase->setStatus(PurchaseStatus::Finished);
        $this->entityManager->persist($purchase);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(
            new PackageWasPurchasedSymfonyEvent($purchase)
        );

        return true;
    }
}
