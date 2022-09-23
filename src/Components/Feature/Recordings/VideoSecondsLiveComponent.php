<?php

namespace App\Components\Feature\Recordings;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Recordings\Video;
use App\Service\Aspect\DateAndTime\DateAndTimeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent('feature_recordings_video_seconds', 'feature/recordings/video_seconds_live_component.html.twig')]
class VideoSecondsLiveComponent extends AbstractController
{
    use DefaultActionTrait;


    #[LiveProp]
    public Video $video;

    public function shouldPoll(): bool
    {
        $isNew = DateAndTimeService::getDateTimeUtc()
                                   ->getTimestamp() - $this->video->getCreatedAt()
                                                                  ->getTimestamp() < 60;

        if ($isNew && is_null($this->video->getAssetFullMp4Seconds())) {
            return true;
        } else {
            return false;
        }
    }

    public function getSeconds(): ?float
    {
        /** @var User $user */
        $user = $this->getUser();

        if (is_null($user)) {
            throw new AccessDeniedHttpException('No valid user.');
        }

        if ($user->getId() !== $this->video->getUser()
                                           ->getId()) {
            throw new AccessDeniedHttpException("Video '{$this->video->getId()}' does not belong to logged in user '{$user->getId()}'.");
        }

        return $this->video->getAssetFullMp4Seconds();
    }
}
