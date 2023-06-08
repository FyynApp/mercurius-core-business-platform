<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\SymfonyEventSubscriber;

use App\VideoBasedMarketing\Recordings\Domain\SymfonyEvent\RecordingSessionWillBeRemovedSymfonyEvent;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


readonly class RecordingSessionWillBeRemovedSymfonyEventSubscriber
    implements EventSubscriberInterface
{
    public function __construct(
        private RecordingsInfrastructureService $recordingsInfrastructureService
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            RecordingSessionWillBeRemovedSymfonyEvent::class => [
                ['handle']
            ],
        ];
    }

    public function handle(
        RecordingSessionWillBeRemovedSymfonyEvent $event
    ): void
    {
        $this
            ->recordingsInfrastructureService
            ->removeRecordingSessionAssets(
                $event->recordingSession
            );
    }
}
