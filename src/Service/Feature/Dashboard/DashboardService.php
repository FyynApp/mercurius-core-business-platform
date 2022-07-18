<?php

namespace App\Service\Feature\Dashboard;

use App\Entity\Feature\Account\User;
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

    /** @return string[] */
    public function getRecordingTitles(User $user): array
    {
        return [
            'First test recording',
            'IBM Sales Pitch for Mr Smith, version 3'
        ];
    }

    public function getNumberOfPresentationpageTemplates(User $user): int
    {
        return sizeof($this->presentationpageTemplatesService->getTemplatesForUser($user));
    }
}
