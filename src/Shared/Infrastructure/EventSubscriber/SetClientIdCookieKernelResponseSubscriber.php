<?php

namespace App\Shared\Infrastructure\EventSubscriber;

use App\Shared\Infrastructure\Enum\CookieName;
use App\Shared\Infrastructure\Service\CookiesService;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;


class SetClientIdCookieKernelResponseSubscriber
    implements EventSubscriberInterface
{
    private CookiesService $cookiesService;

    public function __construct(
        CookiesService  $cookiesService
    )
    {
        $this->cookiesService = $cookiesService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [
                ['setClientIdCookie']
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function setClientIdCookie(
        ResponseEvent $event
    ): void
    {
        // We only handle master requests, no subrequests
        if ($event->getRequestType() === HttpKernelInterface::SUB_REQUEST) {
            return;
        }

        if (!$this->cookiesService->isCookieAllowed(
            $event->getRequest(),
            CookieName::ClientId)
        ) {
            $response = $event->getResponse();
            $response->headers->clearCookie(CookieName::ClientId->value);
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
