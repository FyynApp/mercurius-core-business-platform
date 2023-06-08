<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\SymfonyEventSubscriber;

use App\VideoBasedMarketing\Account\Domain\SymfonyEvent\UnregisteredUserClaimedRegisteredUserSymfonyEvent;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


readonly class UnregisteredUserClaimedRegisteredUserSymfonyEventSubscriber
    implements EventSubscriberInterface
{
    public function __construct(
        private RecordingsInfrastructureService $recordingsInfrastructureService
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            UnregisteredUserClaimedRegisteredUserSymfonyEvent::class => [
                ['handle']
            ],
        ];
    }

    public function handle(
        UnregisteredUserClaimedRegisteredUserSymfonyEvent $event
    ): void
    {
        $this
            ->recordingsInfrastructureService
            ->checkAndHandleVideoAssetGenerationForUser(
                $event->registeredUser,
                false,
                true
            );
    }
}
