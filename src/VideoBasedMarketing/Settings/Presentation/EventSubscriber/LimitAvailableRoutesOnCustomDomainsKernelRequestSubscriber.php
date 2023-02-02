<?php

namespace App\VideoBasedMarketing\Settings\Presentation\EventSubscriber;

use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;


readonly class LimitAvailableRoutesOnCustomDomainsKernelRequestSubscriber
    implements EventSubscriberInterface
{
    public function __construct(
        private RouterInterface $router
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
        $customDomain = $event->getRequest()->headers->get('X-Mercurius-Custom-Domain');

        if (is_null($customDomain)) {
            #throw new Exception($host . ' -- ' . $_ENV['ROUTER_REQUEST_CONTEXT_HOST']);
            return;
        }

        if ($customDomain === $_ENV['ROUTER_REQUEST_CONTEXT_HOST']) {
            #throw new Exception($host . ' -- ' . $_ENV['ROUTER_REQUEST_CONTEXT_HOST']);
            return;
        }

        if ($event->getRequestType() === HttpKernelInterface::SUB_REQUEST) {
            #throw new Exception('B');
            return;
        }

        $routeName = $event->getRequest()->attributes->get('_route');

        $allowedRouteNames = [
            'shared.infrastructure.content_delivery.serve_external_asset',
            'shared.presentation.contentpages.cookie_settings',

            'videobasedmarketing.recordings.presentation.video.share_link',

            'videobasedmarketing.settings.presentation.custom_domain.redirect',

            'ux_live_component',
        ];

        foreach ($allowedRouteNames as $allowedRouteName) {
            if (str_starts_with($routeName, $allowedRouteName)) {
                #throw new Exception('C');
                return;
            }
        }

        #throw new Exception($url);
        $event->setResponse(
            new RedirectResponse(
                $this->router->generate('videobasedmarketing.settings.presentation.custom_domain.redirect')
            )
        );
        $event->stopPropagation();
    }
}
