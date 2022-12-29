<?php

namespace App\VideoBasedMarketing\Account\Domain\EventSubscriber;

use App\VideoBasedMarketing\Account\Domain\Service\AccountDomainService;
use App\VideoBasedMarketing\Account\Infrastructure\Event\EmailVerificationRequestHandledSuccessfullyEvent;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoAssetGenerationDomainService;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class EmailVerificationRequestHandledSuccessfullyInfrastructureEventSubscriber
    implements EventSubscriberInterface
{
    private AccountDomainService $accountDomainService;

    private VideoAssetGenerationDomainService $videoAssetGenerationDomainService;


    public function __construct(
        AccountDomainService              $accountDomainService,
        VideoAssetGenerationDomainService $videoAssetGenerationDomainService
    )
    {
        $this->accountDomainService = $accountDomainService;
        $this->videoAssetGenerationDomainService = $videoAssetGenerationDomainService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailVerificationRequestHandledSuccessfullyEvent::class => [
                ['handle']
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function handle(
        EmailVerificationRequestHandledSuccessfullyEvent $event
    ): void
    {
        $this->accountDomainService->makeUserVerified($event->getUser());
        $this->videoAssetGenerationDomainService->checkAndHandleVideoAssetGeneration($event->getUser());
    }
}
