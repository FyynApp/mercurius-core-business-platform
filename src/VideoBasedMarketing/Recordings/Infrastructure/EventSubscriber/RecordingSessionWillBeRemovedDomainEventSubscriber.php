<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\EventSubscriber;


use App\VideoBasedMarketing\Account\Infrastructure\Event\EmailVerificationRequestHandledSuccessfullyEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RecordingSessionWillBeRemovedDomainEventSubscriber
    implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            EmailVerificationRequestHandledSuccessfullyEvent::class => [
                ['handle']
            ],
        ];
    }
}
