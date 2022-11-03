<?php

namespace App\Message;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;


class EventLoggerMiddleware
    implements MiddlewareInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handle(
        Envelope       $envelope,
        StackInterface $stack
    ): Envelope
    {
        if (null !== $envelope->last(ReceivedStamp::class)) {
            $message = $envelope->getMessage();

            $this->logger->debug("Middleware received message of class " . get_class($message));
        }

        return $stack
            ->next()
            ->handle(
             $envelope,
             $stack
            );
    }
}
