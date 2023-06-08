<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\SymfonyEventSubscriber;

use App\VideoBasedMarketing\Account\Infrastructure\SymfonyEvent\UserAuthenticatedViaThirdPartySymfonyEvent;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


readonly class UserAuthenticatedViaThirdPartySymfonyEventSubscriber
    implements EventSubscriberInterface
{
    public function __construct(
        private RecordingsInfrastructureService $recordingsInfrastructureService
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            UserAuthenticatedViaThirdPartySymfonyEvent::class => [
                ['handle']
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function handle(
        UserAuthenticatedViaThirdPartySymfonyEvent $event
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
