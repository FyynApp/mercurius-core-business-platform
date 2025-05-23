<?php

namespace App\VideoBasedMarketing\Account\Presentation\SymfonyEventSubscriber;

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


readonly class LimitAvailableRoutesForUnregisteredUsersKernelRequestSymfonyEventSubscriber
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

        if ($user->isRegistered() && $user->isVerified()) {
            return;
        }

        $routeName = $event->getRequest()->attributes->get('_route');

        $allowedRouteNames = [
            'shared.infrastructure.content_delivery.serve_external_asset',
            'shared.presentation.contentpages.cookie_settings',
            'shared.presentation.contentpages.features',
            'shared.presentation.styleguide',
            'shared.presentation.contentpages.homepage_native_recorder',
            'shared.presentation.contentpages.homepage',
            'shared.presentation.catchall.root',
            'shared.presentation.catchall.pattern',

            'videobasedmarketing.account.presentation.',
            'videobasedmarketing.account.api.extension.',
            'videobasedmarketing.account.api.native_browser_recorder.',
            'videobasedmarketing.account.infrastructure.thirdpartyauth.',

            'videobasedmarketing.organization.',

            'videobasedmarketing.recordings.api.extension.',
            'videobasedmarketing.recordings.api.native_browser_recorder.',
            'videobasedmarketing.recordings.api.video_upload.',

            'videobasedmarketing.recordings.presentation.dedicated_camera',
            'videobasedmarketing.recordings.presentation.videos.',
            'videobasedmarketing.recordings.presentation.video_folders.',
            'videobasedmarketing.recordings.presentation.upload_video',
            'videobasedmarketing.recordings.presentation.video.deletion',
            'videobasedmarketing.recordings.presentation.recording_session.',
            'videobasedmarketing.recordings.presentation.video.share_link',
            'videobasedmarketing.recordings.presentation.show_native_browser_recorder',
            'videobasedmarketing.recordings.presentation.embeddable_video_player.',

            'videobasedmarketing.recording_requests.',

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
