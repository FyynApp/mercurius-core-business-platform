<?php

namespace App\VideoBasedMarketing\Account\Presentation\SymfonyEventSubscriber;

use App\Shared\Infrastructure\Enum\CookieName;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


class UpdateUiTimezoneKernelRequestSymfonyEventSubscriber
    implements EventSubscriberInterface
{
    private TokenStorageInterface $tokenStorage;

    private EntityManagerInterface $entityManager;

    public function __construct(
        TokenStorageInterface  $tokenStorage,
        EntityManagerInterface $entityManager
    )
    {
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['handle']
            ],
        ];
    }

    public function handle(
        RequestEvent $event
    ): void
    {
        /** @var null|User $user */
        $user = $this->tokenStorage->getToken()?->getUser();

        if (is_null($user)) {
            return;
        }

        if ($event->getRequestType() === HttpKernelInterface::SUB_REQUEST) {
            return;
        }

        $timezoneFromCookie = $event
            ->getRequest()
            ->cookies
            ->get(CookieName::ClientTimezone->value);

        if (   !is_null($timezoneFromCookie)
            && $timezoneFromCookie !== $user->getUiTimezone()
        ) {
            $user->setUiTimezone($timezoneFromCookie);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }
}
