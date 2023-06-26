<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\SymfonyEventSubscriber;

use App\VideoBasedMarketing\LingoSync\Domain\Service\LingoSyncCreditsDomainService;
use App\VideoBasedMarketing\Membership\Domain\SymfonyEvent\MembershipPlanWasSubscribedSymfonyEvent;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


readonly class MembershipPlanWasSubscribedSymfonyEventSubscriber
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
            MembershipPlanWasSubscribedSymfonyEvent::class => [
                ['handle']
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function handle(
        MembershipPlanWasSubscribedSymfonyEvent $event
    ): void
    {
        $subscription = $event->subscription;

        $this
            ->lingoSyncCreditsDomainService
            ->topUpCreditsFromMembershipPlanSubscription($subscription);
    }
}
