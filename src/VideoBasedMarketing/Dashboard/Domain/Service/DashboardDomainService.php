<?php

namespace App\VideoBasedMarketing\Dashboard\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Presentationpages\Domain\Enum\PresentationpageType;
use App\VideoBasedMarketing\Presentationpages\Domain\Service\PresentationpagesService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoDomainService;


class DashboardDomainService
{
    private PresentationpagesService $presentationpagesService;

    private VideoDomainService $videoDomainService;

    public function __construct(
        PresentationpagesService $presentationpagesService,
        VideoDomainService $videoDomainService
    )
    {
        $this->presentationpagesService = $presentationpagesService;
        $this->videoDomainService = $videoDomainService;
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
        $videos = $this->videoDomainService->getAvailableVideos($user);

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
