<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\SymfonyEventSubscriber;

use App\VideoBasedMarketing\Account\Infrastructure\SymfonyEvent\UserVerifiedSymfonyEvent;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class UserVerifiedSymfonyEventSubscriber
    implements EventSubscriberInterface
{
    private RecordingsInfrastructureService $recordingsInfrastructureService;


    public function __construct(
        RecordingsInfrastructureService   $recordingsInfrastructureService
    )
    {
        $this->recordingsInfrastructureService = $recordingsInfrastructureService;
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
            ->recordingsInfrastructureService
            ->checkAndHandleVideoAssetGenerationForUser(
                $event->getUser(),
                false,
                true
            );
    }
}
