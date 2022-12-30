<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\EventSubscriber;

use App\VideoBasedMarketing\Account\Domain\Event\UserHasBeenVerifiedEvent;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoAssetGenerationDomainService;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


readonly class UserHasBeenVerifiedEventSubscriber
    implements EventSubscriberInterface
{


    public function __construct(
        private VideoAssetGenerationDomainService $videoAssetGenerationDomainService
    )
    {}

    public static function getSubscribedEvents(): array
    {
        return [
            UserHasBeenVerifiedEvent::class => [
                ['handle']
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function handle(
        UserHasBeenVerifiedEvent $event
    ): void
    {
        $this
            ->videoAssetGenerationDomainService
            ->checkAndHandleVideoAssetGeneration(
                $event->user
            );
    }
}
