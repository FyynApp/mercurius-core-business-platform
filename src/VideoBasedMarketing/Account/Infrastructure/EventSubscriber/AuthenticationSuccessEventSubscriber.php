<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\EventSubscriber;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Service\AccountDomainService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;


class AuthenticationSuccessEventSubscriber
    implements EventSubscriberInterface
{
    private AccountDomainService $accountDomainService;

    private LoggerInterface $logger;

    private Security $security;


    public function __construct(
        AccountDomainService  $accountDomainService,
        LoggerInterface       $logger,
        Security              $security
    )
    {
        $this->accountDomainService = $accountDomainService;
        $this->logger = $logger;
        $this->security = $security;
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
