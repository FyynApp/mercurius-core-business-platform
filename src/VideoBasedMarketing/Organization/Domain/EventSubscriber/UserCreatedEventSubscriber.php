<?php

namespace App\VideoBasedMarketing\Organization\Domain\EventSubscriber;

use App\VideoBasedMarketing\Account\Domain\Event\UserCreatedEvent;
use App\VideoBasedMarketing\Organization\Domain\Service\OrganizationDomainService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


readonly class UserCreatedEventSubscriber
    implements EventSubscriberInterface
{
    public function __construct(
        private OrganizationDomainService $organizationDomainService,
        private EntityManagerInterface    $entityManager
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            UserCreatedEvent::class => [
                ['handle']
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function handle(
        UserCreatedEvent $event
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
