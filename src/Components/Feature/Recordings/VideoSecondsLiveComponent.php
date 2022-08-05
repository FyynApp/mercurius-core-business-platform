<?php

namespace App\Components\Feature\Recordings;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Recordings\Video;
use App\Service\Aspect\DateAndTime\DateAndTimeService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('feature_recordings_video_seconds', 'feature/recordings/video_seconds_live_component.html.twig')]
class VideoSecondsLiveComponent
{
    use DefaultActionTrait;

    #[LiveProp]
    public Video $video;

    #[LiveProp]
    public ?User $user;

    public function shouldPoll(): bool
    {
        if (is_null($this->user)) {
            throw new AccessDeniedHttpException('No logged in user.');
        }

        if ($this->user->getId() !== $this->video->getUser()->getId()) {
            throw new AccessDeniedHttpException("Video '{$this->video->getId()}' does not belong to user '{$this->user->getId()}'.");
        }

        if (!is_null($this->video->getAssetFullMp4Seconds()))
        {
            return false;
        }

        return DateAndTimeService::getDateTimeUtc()->getTimestamp() - $this->video->getCreatedAt()->getTimestamp() < 60;
    }
}
