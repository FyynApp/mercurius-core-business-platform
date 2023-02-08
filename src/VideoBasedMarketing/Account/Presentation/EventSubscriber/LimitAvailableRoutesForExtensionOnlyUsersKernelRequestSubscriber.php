<?php

namespace App\VideoBasedMarketing\Account\Presentation\EventSubscriber;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


readonly class LimitAvailableRoutesForExtensionOnlyUsersKernelRequestSubscriber
    implements EventSubscriberInterface
{
    public function __construct(
        private RouterInterface       $router,
        private TokenStorageInterface $tokenStorage,
        private CapabilitiesService   $capabilitiesService
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['handle']
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function handle(
        RequestEvent $event
    ): void
    {
        if ($event->getRequestType() === HttpKernelInterface::SUB_REQUEST) {
            return;
        }

        /** @var null|User $user */
        $user = $this->tokenStorage->getToken()?->getUser();

        if (is_null($user)) {
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
            'videobasedmarketing.recordings.api.video_upload.',
            'videobasedmarketing.recordings.presentation.videos.',
            'videobasedmarketing.recordings.presentation.video.deletion',
            'videobasedmarketing.recordings.presentation.recording_session.recording_preview.asset_redirect',
            'videobasedmarketing.recordings.presentation.recording_session.extension',
            'videobasedmarketing.recordings.presentation.video.share_link',
            'videobasedmarketing.recordings.presentation.show_native_browser_recorder',

            'videobasedmarketing.mailings.presentation.create_video_mailing',
            'videobasedmarketing.mailings.presentation.show_video_mailing_editor',
            'videobasedmarketing.mailings.presentation.send_video_mailing',

            'videobasedmarketing.membership.presentation.show_upgrade_offer.',
            'videobasedmarketing.membership.presentation.subscription.',
            'videobasedmarketing.membership.infrastructure.subscription.',

            'videobasedmarketing.settings.presentation.',
            'videobasedmarketing.settings.api.logo_upload.',

            'ux_live_component',
        ];

        if ($this->capabilitiesService->canAdministerVideos($user)) {
            $allowedRouteNames[] = 'videobasedmarketing.recordings.presentation.admin.videos.';
        }

        foreach ($allowedRouteNames as $allowedRouteName) {
            if (str_starts_with($routeName, $allowedRouteName)) {
                return;
            }
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
