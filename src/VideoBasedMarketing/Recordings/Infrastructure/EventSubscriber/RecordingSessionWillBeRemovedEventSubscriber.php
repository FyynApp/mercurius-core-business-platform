<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\EventSubscriber;

use App\VideoBasedMarketing\Recordings\Domain\Event\RecordingSessionWillBeRemovedEvent;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


readonly class RecordingSessionWillBeRemovedEventSubscriber
    implements EventSubscriberInterface
{
    public function __construct(
        private RecordingsInfrastructureService $recordingsInfrastructureService
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            RecordingSessionWillBeRemovedEvent::class => [
                ['handle']
            ],
        ];
    }

    public function handle(
        RecordingSessionWillBeRemovedEvent $event
    ): void
    {
        $this
            ->recordingsInfrastructureService
            ->removeRecordingSessionAssets(
                $event->recordingSession
            );
    }
}
