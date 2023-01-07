<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\EventSubscriber;

use App\VideoBasedMarketing\Account\Infrastructure\Event\UserAuthenticatedViaThirdPartyEvent;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


readonly class UserAuthenticatedViaThirdPartyEventSubscriber
    implements EventSubscriberInterface
{
    public function __construct(
        private RecordingsInfrastructureService $recordingsInfrastructureService
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            UserAuthenticatedViaThirdPartyEvent::class => [
                ['handle']
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function handle(
        UserAuthenticatedViaThirdPartyEvent $event
    ): void
    {
        $this
            ->recordingsInfrastructureService
            ->checkAndHandleVideoAssetGeneration($event->getUser());
    }
}
