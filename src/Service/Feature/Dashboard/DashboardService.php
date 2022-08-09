<?php

namespace App\Service\Feature\Dashboard;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Recordings\Video;
use App\Service\Feature\PresentationpageTemplates\PresentationpageTemplatesService;
use App\Service\Feature\Recordings\VideoService;

class DashboardService
{
    private PresentationpageTemplatesService $presentationpageTemplatesService;

    private VideoService $videoService;

    public function __construct(
        PresentationpageTemplatesService $presentationpageTemplatesService,
        VideoService $videoService
    )
    {
        $this->presentationpageTemplatesService = $presentationpageTemplatesService;
        $this->videoService = $videoService;
    }

    public function getFirstName(User $user): string
    {
        return $user->getFirstName();
    }

    public function getLastName(User $user): string
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

    public function getNumberOfPresentationpageTemplates(User $user): int
    {
        return sizeof($this->presentationpageTemplatesService->getTemplatesForUser($user));
    }
}
