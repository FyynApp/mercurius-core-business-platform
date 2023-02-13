<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\EventSubscriber;

use App\VideoBasedMarketing\Account\Domain\Event\UnregisteredUserClaimedRegisteredUserEvent;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


readonly class UnregisteredUserClaimedRegisteredUserEventSubscriber
    implements EventSubscriberInterface
{
    public function __construct(
        private RecordingsInfrastructureService $recordingsInfrastructureService
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            UnregisteredUserClaimedRegisteredUserEvent::class => [
                ['handle']
            ],
        ];
    }

    public function handle(
        UnregisteredUserClaimedRegisteredUserEvent $event
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
