<?php

namespace App\VideoBasedMarketing\Presentationpages\Domain\SymfonyEventListener;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Presentationpages\Domain\Service\PresentationpagesService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener]
class LoginSuccessSymfonyEventListener
{
    private PresentationpagesService $presentationpagesService;

    public function __construct(
        PresentationpagesService $presentationpagesService
    )
    {
        $this->presentationpagesService = $presentationpagesService;
    }


    public function __invoke(LoginSuccessEvent $event): void
    {
        /** @var User $user */
        $user = $event->getUser();

        $this
            ->presentationpagesService
            ->createBasicSetOfVideoOnlyPresentationpageTemplatesForUserIfNotExist(
                $user
            );
    }
}
