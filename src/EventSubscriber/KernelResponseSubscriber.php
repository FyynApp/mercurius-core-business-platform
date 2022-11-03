<?php

namespace App\EventSubscriber;

use App\Service\Aspect\Cookies\CookieName;
use App\Service\Aspect\Cookies\CookiesService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;


class KernelResponseSubscriber
    implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    private CookiesService $cookiesService;

    public function __construct(
        LoggerInterface $logger,
        CookiesService  $cookiesService
    )
    {
        $this->logger = $logger;
        $this->cookiesService = $cookiesService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [
                ['conditionallyDisableWebDebugToolbar', -127],
                ['setClientIdCookie', 0]
            ],
        ];
    }

    public function conditionallyDisableWebDebugToolbar(ResponseEvent $event): void
    {
        $request = $event->getRequest();

        if (mb_substr($request->getRequestUri(), 3, 19) === '/presentationpages/') {
            $this->logger->info("Conditionally disabling Web Debug Toolbar by removing X-Debug-Token header.");

            $response = $event->getResponse();

            $response->headers->remove('x-debug-token');
            $response->headers->add(['x-removed-debug-token' => 'yes']);

            $event->setResponse($response);
        }
    }

    /**
     * @throws Exception
     */
    public function setClientIdCookie(ResponseEvent $event): void
    {
        // We only handle master requests, no subrequests
        if ($event->getRequestType() === HttpKernelInterface::SUB_REQUEST) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        if (is_null($request->cookies->get(CookieName::ClientId->value))) {
            $clientId = bin2hex(random_bytes(16));
            $response->headers->setCookie(
                $this->cookiesService::createCookieObject(
                    CookieName::ClientId,
                    $clientId,
                    $this->cookiesService::getCookieExpireValue(CookieName::ClientId)
                )
            );
        } else {
            $response->headers->setCookie(
                $this->cookiesService::createCookieObject(
                    CookieName::ClientId,
                    $request->cookies->get(CookieName::ClientId->value),
                    $this->cookiesService::getCookieExpireValue(CookieName::ClientId)
                )
            );
        }
    }
}
