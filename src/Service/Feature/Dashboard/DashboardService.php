<?php

namespace App\Service\Feature\Dashboard;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Recordings\Video;
use App\Service\Feature\PresentationpageTemplates\PresentationpageTemplatesService;

class DashboardService
{
    private PresentationpageTemplatesService $presentationpageTemplatesService;

    public function __construct(PresentationpageTemplatesService $presentationpageTemplatesService)
    {
        $this->presentationpageTemplatesService = $presentationpageTemplatesService;
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
        $videos = $user->getVideos()->slice(0, 3);
        rsort($videos);
        return $videos;
    }

    public function getNumberOfPresentationpageTemplates(User $user): int
    {
        return sizeof($this->presentationpageTemplatesService->getTemplatesForUser($user));
    }
}
