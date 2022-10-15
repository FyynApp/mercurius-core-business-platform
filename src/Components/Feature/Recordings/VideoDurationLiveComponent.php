<?php

namespace App\Components\Feature\Recordings;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Recordings\Video;
use App\Security\VotingAttribute;
use App\Service\Aspect\DateAndTime\DateAndTimeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    'feature_recordings_video_duration',
    'feature/recordings/video_duration_live_component.html.twig'
)]
class VideoDurationLiveComponent extends AbstractController
{
    use DefaultActionTrait;


    #[LiveProp]
    public Video $video;

    public function shouldPoll(): bool
    {
        $isNew =
            DateAndTimeService::getDateTimeUtc()->getTimestamp()
            - $this->video->getCreatedAt()->getTimestamp()
            < 60;

        if ($isNew && is_null($this->video->getDuration())) {
            return true;
        } else {
            return false;
        }
    }

    public function getDuration(): ?string
    {
        $this->denyAccessUnlessGranted(VotingAttribute::View->value, $this->video);

        return $this->video->getDuration();
    }
}
