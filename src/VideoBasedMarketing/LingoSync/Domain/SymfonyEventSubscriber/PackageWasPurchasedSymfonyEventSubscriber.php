<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\SymfonyEventSubscriber;

use App\VideoBasedMarketing\LingoSync\Domain\Entity\LingoSyncCreditPosition;
use App\VideoBasedMarketing\Membership\Domain\Enum\PackageName;
use App\VideoBasedMarketing\Membership\Domain\SymfonyEvent\PackageWasPurchasedSymfonyEvent;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


readonly class PackageWasPurchasedSymfonyEventSubscriber
    implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PackageWasPurchasedSymfonyEvent::class => [
                ['handle']
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function handle(
        PackageWasPurchasedSymfonyEvent $event
    ): void
    {
        $purchase = $event->purchase;

        $creditsAmount = match ($purchase->getPackageName()) {
            PackageName::FreeLingoSyncCreditsFor10Minutes, PackageName::LingoSyncCreditsFor10Minutes => 10,
            PackageName::LingoSyncCreditsFor5Minutes => 5,

            default => null,
        };

        if (is_null($creditsAmount)) {
            return;
        }

        $lingoSyncCreditPosition = new LingoSyncCreditPosition(
            $creditsAmount,
            null,
            $purchase
        );

        $this->entityManager->persist($lingoSyncCreditPosition);
        $this->entityManager->flush();
    }
}
