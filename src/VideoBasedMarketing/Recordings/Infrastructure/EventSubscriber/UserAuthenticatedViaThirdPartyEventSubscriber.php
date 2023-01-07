<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\EventSubscriber;

use App\VideoBasedMarketing\Account\Infrastructure\Event\UserAuthenticatedViaThirdPartyEvent;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class UserAuthenticatedViaThirdPartyEventSubscriber
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
