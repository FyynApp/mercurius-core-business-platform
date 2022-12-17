<?php

namespace App\VideoBasedMarketing\Account\Presentation\EventSubscriber;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


class ForceShowingEmailVerificationPageKernelRequestSubscriber
    implements EventSubscriberInterface
{
    private RouterInterface $router;

    private TokenStorageInterface $tokenStorage;

    public function __construct(
        RouterInterface       $router,
        TokenStorageInterface $tokenStorage
    )
    {
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['redirectToEmailVerificationPage']
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function redirectToEmailVerificationPage(
        RequestEvent $event
    ): void
    {
        // We only handle master requests, no subrequests
        if ($event->getRequestType() === HttpKernelInterface::SUB_REQUEST) {
            return;
        }

        /** @var User $user */
        $user = $this->tokenStorage->getToken()?->getUser();

        if (is_null($user)) {
            return;
        }

        if (    $event->getRequest()->attributes->get('_route')
            === 'videobasedmarketing.account.presentation.claim_unregistered_user.please_verify_email_address'
        ) {
            return;
        }

        if ($user->isRegistered() && !$user->isVerified()) {
            $response = new RedirectResponse(
                $this->router->generate(
                    'videobasedmarketing.account.presentation.claim_unregistered_user.please_verify_email_address'
                )
            );
            $event->setResponse($response);
        }
    }
}
