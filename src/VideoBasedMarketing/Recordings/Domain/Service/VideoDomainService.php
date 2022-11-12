<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;


class VideoDomainService
{
    /**
     * @return Video[]
     */
    public function getAvailableVideos(User $user): array
    {
        /** @var Video[] $allVideos */
        $allVideos = $user->getVideos()->toArray();

        $videos = [];
        foreach ($allVideos as $video) {
            if (!$video->isDeleted()) {
                $videos[] = $video;
            }
        }

        rsort($videos);

        return $videos;
    }
}
