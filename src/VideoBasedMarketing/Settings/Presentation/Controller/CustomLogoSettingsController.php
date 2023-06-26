<?php

namespace App\VideoBasedMarketing\Settings\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Enum\AccessAttribute;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\Membership\Domain\Enum\Capability;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipPlanService;
use App\VideoBasedMarketing\Settings\Domain\Service\SettingsDomainService;
use App\VideoBasedMarketing\Settings\Infrastructure\Entity\LogoUpload;
use App\VideoBasedMarketing\Settings\Infrastructure\Service\SettingsInfrastructureService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class CustomLogoSettingsController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/settings/custom-logo-on-landingpages',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/einstellungen/eigenes-logo-auf-landingpages',
        ],
        name        : 'videobasedmarketing.settings.presentation.custom_logo',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function customLogoAction(
        MembershipPlanService         $membershipPlanService,
        CapabilitiesService           $capabilitiesService,
        SettingsInfrastructureService $settingsInfrastructureService
    ): Response
    {
        $user = $this->getUser();

        if (!$capabilitiesService->canEditCustomLogoSetting($user)) {
            throw $this->createAccessDeniedException("User '{$user->getId()} cannot edit custom logo setting.");
        }

        return $this->render(
            '@videobasedmarketing.settings/custom_logo.html.twig',
            [
                'hasCapability' => $capabilitiesService->canPresentOwnLogoOnLandingpage($user),

                'requiredMembershipPlan' => $membershipPlanService
                    ->getCheapestMembershipPlanRequiredForCapabilities([
                        Capability::CustomLogoOnLandingpage
                    ]),

                'logoUploads' => $settingsInfrastructureService->getLogoUploads($user)
            ]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/settings/custom-logo/logo-upload/{logoUploadId}/activation',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/einstellungen/eigenes-logo/logo-upload/{logoUploadId}/aktivierung',
        ],
        name        : 'videobasedmarketing.settings.presentation.custom_logo.activate_logo_upload',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function activateLogoUploadAction(
        string $logoUploadId,
        SettingsDomainService $settingsDomainService
    ): Response
    {
        $r = $this->verifyAndGetOrganizationAndEntity(
            LogoUpload::class,
            $logoUploadId,
            AccessAttribute::Edit
        );

        /** @var LogoUpload $logoUpload */
        $logoUpload = $r->getEntity();

        $settingsDomainService->makeLogoUploadActive($logoUpload);

        return $this->redirectToRoute('videobasedmarketing.settings.presentation.custom_logo');
    }
}
