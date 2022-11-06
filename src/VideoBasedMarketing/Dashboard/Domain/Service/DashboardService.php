<?php

namespace App\VideoBasedMarketing\Dashboard\Domain\Service;

use App\Entity\Feature\Presentationpages\PresentationpageType;
use App\Entity\Feature\Recordings\Video;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Presentationpages\Domain\Service\PresentationpagesService;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoService;


class DashboardService
{
    private PresentationpagesService $presentationpagesService;

    private VideoService $videoService;

    public function __construct(
        PresentationpagesService $presentationpagesService,
        VideoService             $videoService
    )
    {
        $this->presentationpagesService = $presentationpagesService;
        $this->videoService = $videoService;
    }

    public function getFirstName(User $user): ?string
    {
        return $user->getFirstName();
    }

    public function getLastName(User $user): ?string
    {
        return $user->getLastName();
    }

    public function getEmail(User $user): string
    {
        return $user->getEmail();
    }

    /** @return Video[] */
    public function getLatestVideos(User $user): array
    {
        $videos = $this->videoService->getAvailableVideos($user);

        return array_slice($videos, 0, 3);
    }

    public function getNumberOfPresentationpages(
        User                 $user,
        PresentationpageType $type
    ): int
    {
        return sizeof($this->presentationpagesService->getPresentationpagesForUser($user, $type));
    }
}
