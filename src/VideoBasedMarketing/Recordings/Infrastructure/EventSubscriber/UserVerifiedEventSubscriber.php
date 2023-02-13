<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\EventSubscriber;

use App\VideoBasedMarketing\Account\Infrastructure\Event\UserVerifiedEvent;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class UserVerifiedEventSubscriber
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
            UserVerifiedEvent::class => [
                ['handle']
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function handle(
        UserVerifiedEvent $event
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
