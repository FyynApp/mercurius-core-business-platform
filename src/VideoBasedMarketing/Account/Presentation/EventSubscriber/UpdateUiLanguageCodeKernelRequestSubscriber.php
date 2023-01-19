<?php

namespace App\VideoBasedMarketing\Account\Presentation\EventSubscriber;

use App\Shared\Domain\Enum\Iso639_1Code;
use App\Shared\Domain\Service\Iso639_1CodeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


class UpdateUiLanguageCodeKernelRequestSubscriber
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

        $pathInfo = $event->getRequest()->getPathInfo();
        $pathInfoSegments = explode('/', $pathInfo);

        $languageCode = null;

        if (sizeof($pathInfoSegments) > 1) {
            $languageCode = Iso639_1Code::tryFrom($pathInfoSegments[1]);
        }

        if (   is_null($languageCode)
            && is_null($user->getUiLanguageCode())
        ) {
            $languageCode = Iso639_1CodeService::getCodeFromRequest($event->getRequest());
        }

        if (!is_null($languageCode)) {
            $user->setUiLanguageCode($languageCode);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }
}
