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
        $purchase->setStatus(PurchaseStatus::Finished);
        $this->entityManager->persist($purchase);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(
            new PackageWasPurchasedSymfonyEvent($purchase)
        );

        return true;
    }
}
