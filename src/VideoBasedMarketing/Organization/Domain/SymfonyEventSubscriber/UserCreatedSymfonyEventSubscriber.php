<?php

namespace App\VideoBasedMarketing\Organization\Domain\SymfonyEventSubscriber;

use App\VideoBasedMarketing\Account\Domain\SymfonyEvent\UserCreatedSymfonyEvent;
use App\VideoBasedMarketing\Organization\Domain\Service\OrganizationDomainService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


readonly class UserCreatedSymfonyEventSubscriber
    implements EventSubscriberInterface
{
    public function __construct(
        private OrganizationDomainService $organizationDomainService,
        private EntityManagerInterface    $entityManager
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            UserCreatedSymfonyEvent::class => [
                ['handle']
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function handle(
        UserCreatedSymfonyEvent $event
    ): void
    {
        $organization = $this
            ->organizationDomainService
            ->createOrganization($event->user);

        $event
            ->user
            ->setCurrentlyActiveOrganization($organization);

        $this->entityManager->persist($event->user);
        $this->entityManager->flush();
    }
}
