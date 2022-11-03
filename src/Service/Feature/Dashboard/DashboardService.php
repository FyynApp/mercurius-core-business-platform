<?php

namespace App\Service\Feature\Dashboard;

use App\BoundedContext\Account\Domain\Entity\User;
use App\Entity\Feature\Presentationpages\PresentationpageType;
use App\Entity\Feature\Recordings\Video;
use App\Service\Feature\Presentationpages\PresentationpagesService;
use App\Service\Feature\Recordings\VideoService;


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

    public function getSubscriptionPlan(User $user): string
    {
        return 'm';
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
