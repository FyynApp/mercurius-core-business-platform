<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\SymfonyEventSubscriber;

use App\VideoBasedMarketing\LingoSync\Domain\Entity\LingoSyncCreditPosition;
use App\VideoBasedMarketing\Membership\Domain\Enum\MembershipPlanName;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipPlanService;
use App\VideoBasedMarketing\Membership\Domain\SymfonyEvent\MembershipPlanWasSubscribedSymfonyEvent;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use ValueError;


readonly class MembershipPlanWasSubscribedSymfonyEventSubscriber
    implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MembershipPlanService  $membershipPlanService
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

        $membershipPlan = $this
            ->membershipPlanService
            ->getMembershipPlanByName(
                $subscription->getMembershipPlanName()
            );

        $creditsAmount = $membershipPlan->getAmountOfLingoSyncCreditsPerMonth();

        $lingoSyncCreditPosition = new LingoSyncCreditPosition(
            $creditsAmount,
            $subscription
        );

        $this->entityManager->persist($lingoSyncCreditPosition);
        $this->entityManager->flush();
    }
}
