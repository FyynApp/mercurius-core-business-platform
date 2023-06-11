<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\SymfonyEventSubscriber;

use App\VideoBasedMarketing\AudioTranscription\Domain\SymfonyEvent\WebVttBecameAvailableSymfonyEvent;
use App\VideoBasedMarketing\LingoSync\Domain\Service\LingoSyncDomainService;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


readonly class WebVttBecameAvailableSymfonyEventSubscriber
    implements EventSubscriberInterface
{
    public function __construct(
        private LingoSyncDomainService $lingoSyncDomainService
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WebVttBecameAvailableSymfonyEvent::class => [
                ['handle']
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function handle(
        WebVttBecameAvailableSymfonyEvent $event
    ): void
    {
        $this
            ->lingoSyncDomainService
            ->handleWebVttBecameAvailable($event->webVtt);
    }
}
