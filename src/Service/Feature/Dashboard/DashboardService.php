<?php

namespace App\Service\Feature\Dashboard;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Recordings\RecordingSession;
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

    /** @return RecordingSession[] */
    public function getLatestRecordingSessions(User $user): array
    {
        return $user->getRecordingSessions()->slice(0, 3);
    }

    public function getNumberOfPresentationpageTemplates(User $user): int
    {
        return sizeof($this->presentationpageTemplatesService->getTemplatesForUser($user));
    }
}
