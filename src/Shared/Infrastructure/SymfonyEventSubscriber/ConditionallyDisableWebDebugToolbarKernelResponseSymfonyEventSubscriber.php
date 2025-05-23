<?php

namespace App\Shared\Infrastructure\SymfonyEventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;


class ConditionallyDisableWebDebugToolbarKernelResponseSymfonyEventSubscriber
    implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger
    )
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [
                ['conditionallyDisableWebDebugToolbar']
            ],
        ];
    }

    public function conditionallyDisableWebDebugToolbar(
        ResponseEvent $event
    ): void
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
}
