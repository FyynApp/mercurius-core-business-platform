<?php

namespace App\VideoBasedMarketing\Account\Presentation\EventSubscriber;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Service\AccountDomainService;
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

    private AccountDomainService $accountDomainService;

    public function __construct(
        RouterInterface       $router,
        TokenStorageInterface $tokenStorage,
        AccountDomainService  $accountDomainService
    )
    {
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
        $this->accountDomainService = $accountDomainService;
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
        if ($event->getRequestType() === HttpKernelInterface::SUB_REQUEST) {
            return;
        }

        $routeName = $event->getRequest()->attributes->get('_route');

        $allowedRouteNames = [
            'shared.infrastructure.content_delivery.serve_external_asset',
            'videobasedmarketing.account.presentation.claim_unregistered_user.please_verify_email_address',
            'videobasedmarketing.account.presentation.sign_up.email_verification',
            'videobasedmarketing.account.api.extension.',
            'videobasedmarketing.recordings.api.extension.',
            'videobasedmarketing.recordings.presentation.show_video_landingpage',
            'ux_live_component'
        ];

        foreach ($allowedRouteNames as $allowedRouteName) {
            if (str_starts_with($routeName, $allowedRouteName)) {
                return;
            }
        }

        /** @var null|User $user */
        $user = $this->tokenStorage->getToken()?->getUser();

        if (is_null($user)) {
            return;
        }

        if ($this->accountDomainService->userMustVerifyEmailBeforeUsingSite($user)) {
            $response = new RedirectResponse(
                $this->router->generate(
                    'videobasedmarketing.account.presentation.claim_unregistered_user.please_verify_email_address'
                )
            );
            $event->setResponse($response);
        }
    }
}
