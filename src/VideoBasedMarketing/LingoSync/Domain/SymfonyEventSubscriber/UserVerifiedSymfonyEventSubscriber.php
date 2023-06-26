<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\SymfonyEventSubscriber;

use App\VideoBasedMarketing\Account\Infrastructure\SymfonyEvent\UserVerifiedSymfonyEvent;
use App\VideoBasedMarketing\LingoSync\Domain\Service\LingoSyncCreditsDomainService;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class UserVerifiedSymfonyEventSubscriber
    implements EventSubscriberInterface
{
    private LingoSyncCreditsDomainService $lingoSyncCreditsDomainService;


    public function __construct(
        LingoSyncCreditsDomainService $lingoSyncCreditsDomainService
    )
    {
        $this->lingoSyncCreditsDomainService = $lingoSyncCreditsDomainService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserVerifiedSymfonyEvent::class => [
                ['handle']
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function handle(
        UserVerifiedSymfonyEvent $event
    ): void
    {
        $this
            ->lingoSyncCreditsDomainService
            ->topUpCreditsFromUserVerification(
                $event->getUser()
            );
    }
}
