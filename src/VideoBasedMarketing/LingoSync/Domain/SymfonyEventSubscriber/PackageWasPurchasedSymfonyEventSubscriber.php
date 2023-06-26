<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\SymfonyEventSubscriber;

use App\VideoBasedMarketing\LingoSync\Domain\Service\LingoSyncCreditsDomainService;
use App\VideoBasedMarketing\Membership\Domain\SymfonyEvent\PackageWasPurchasedSymfonyEvent;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


readonly class PackageWasPurchasedSymfonyEventSubscriber
    implements EventSubscriberInterface
{
    public function __construct(
        private LingoSyncCreditsDomainService $lingoSyncCreditsDomainService
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

        $this
            ->lingoSyncCreditsDomainService
            ->topUpCreditsFromPackagePurchase($purchase);
    }
}
