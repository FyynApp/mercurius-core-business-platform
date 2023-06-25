<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Component;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Enum\AccessAttribute;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;


#[AsLiveComponent(
    'videobasedmarketing_recordings_video_duration',
    '@videobasedmarketing.recordings/video_duration_live_component.html.twig'
)]
class VideoDurationLiveComponent
    extends AbstractController
{
    use DefaultActionTrait;


    #[LiveProp]
    public Video $video;

    /**
     * @throws Exception
     */
    public function shouldPoll(): bool
    {
        $isNew =
            DateAndTimeService::getDateTime()->getTimestamp()
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
        $this->denyAccessUnlessGranted(AccessAttribute::View->value, $this->video);

        return $this->video->getDuration();
    }
}
