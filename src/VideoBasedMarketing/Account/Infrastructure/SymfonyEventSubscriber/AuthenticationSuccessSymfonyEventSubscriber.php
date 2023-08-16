<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\SymfonyEventSubscriber;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Service\AccountDomainService;
use Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;


readonly class AuthenticationSuccessSymfonyEventSubscriber
    implements EventSubscriberInterface
{
    public function __construct(
        private AccountDomainService $accountDomainService,
        private Security             $security
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AuthenticationSuccessEvent::class => [
                ['handle']
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function handle(
        AuthenticationSuccessEvent $event
    ): void
    {
        /** @var null|User $currentUser */
        $currentUser = $this->security->getUser();

        /** @var null|User $userBeingAuthenticated */
        $userBeingAuthenticated = $event->getAuthenticationToken()->getUser();

        if (   !is_null($currentUser)
            && !is_null($userBeingAuthenticated)
            && $currentUser->getId() !== $userBeingAuthenticated->getId()
            && $currentUser->isUnregistered()
            && $userBeingAuthenticated->isRegistered()
        ) {
            $success = $this->accountDomainService->unregisteredUserClaimsRegisteredUser(
                $currentUser,
                $userBeingAuthenticated
            );

            if (!$success) {
                throw new Exception('AccountDomainService::unregisteredUserClaimsRegisteredUser failed.');
            }
        }
    }
}
