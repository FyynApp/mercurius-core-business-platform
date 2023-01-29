<?php

namespace App\VideoBasedMarketing\Settings\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Enum\Capability;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CustomLogoSettingsController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/settings/custom-logo',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/einstellungen/eigenes-logo',
        ],
        name        : 'videobasedmarketing.settings.presentation.custom_logo',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function customLogoAction(
        MembershipService   $membershipService,
        CapabilitiesService $capabilitiesService
    ): Response
    {
        $user = $this->getUser();

        return $this->render(
            '@videobasedmarketing.settings/custom_logo.html.twig',
            [
                'hasCapability' => $capabilitiesService->hasCapability(
                    $user, Capability::CustomLogoOnLandingpage
                ),
                'requiredMembershipPlan' => $membershipService
                    ->getCheapestMembershipPlanRequiredForCapabilities([
                        Capability::CustomLogoOnLandingpage
                    ])
            ]
        );
    }
}
