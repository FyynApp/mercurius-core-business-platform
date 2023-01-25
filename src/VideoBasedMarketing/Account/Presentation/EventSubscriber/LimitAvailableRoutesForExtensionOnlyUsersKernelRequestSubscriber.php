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


class LimitAvailableRoutesForExtensionOnlyUsersKernelRequestSubscriber
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
                ['limit']
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function limit(
        RequestEvent $event
    ): void
    {
        if ($event->getRequestType() === HttpKernelInterface::SUB_REQUEST) {
            return;
        }

        $routeName = $event->getRequest()->attributes->get('_route');

        $allowedRouteNames = [
            'shared.infrastructure.content_delivery.serve_external_asset',
            'shared.presentation.contentpages.cookie_settings',
            'shared.presentation.styleguide',
            'videobasedmarketing.account.presentation.claim_unregistered_user.',
            'videobasedmarketing.account.api.extension.',
            'videobasedmarketing.account.presentation.sign',
            'videobasedmarketing.account.infrastructure.thirdpartyauth.',
            'videobasedmarketing.recordings.api.extension.',
            'videobasedmarketing.recordings.presentation.videos.',
            'videobasedmarketing.recordings.presentation.video.deletion',
            'videobasedmarketing.recordings.presentation.recording_session.recording_preview.asset_redirect',
            'videobasedmarketing.recordings.presentation.recording_session.extension',
            'videobasedmarketing.recordings.presentation.video.show_with_video_only_presentationpage_template',
            'videobasedmarketing.recordings.presentation.video.share_link',
            'videobasedmarketing.mailings.presentation.create_video_mailing',
            'videobasedmarketing.mailings.presentation.show_video_mailing_editor',
            'videobasedmarketing.mailings.presentation.send_video_mailing',
            'ux_live_component',
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

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isExtensionOnly()) {

            if ($user->isRegistered() && $user->isVerified()) {
                $response = new RedirectResponse(
                    $this->router->generate(
                        'videobasedmarketing.recordings.presentation.videos.overview'
                    )
                );
                $event->setResponse($response);
            }

            if (!$user->isRegistered()) {
                $response = new RedirectResponse(
                    $this->router->generate(
                        'videobasedmarketing.account.presentation.claim_unregistered_user.landingpage'
                    )
                );
                $event->setResponse($response);
            }
        }
    }
}
