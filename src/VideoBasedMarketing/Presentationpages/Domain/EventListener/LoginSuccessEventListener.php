<?php

namespace App\VideoBasedMarketing\Presentationpages\Domain\EventListener;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Presentationpages\Domain\Service\PresentationpagesService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener]
class LoginSuccessEventListener
{
    private PresentationpagesService $presentationpagesService;

    private LoggerInterface $logger;

    public function __construct(
        PresentationpagesService $presentationpagesService,
        LoggerInterface          $logger
    )
    {
        $this->presentationpagesService = $presentationpagesService;
        $this->logger = $logger;
    }


    public function __invoke(LoginSuccessEvent $event): void
    {
        $this->logger->debug('Called!');

        /** @var User $user */
        $user = $event->getUser();

        $this
            ->presentationpagesService
            ->createBasicSetOfVideoOnlyPresentationpageTemplatesForUserIfNotExist(
                $user
            );
    }
}
