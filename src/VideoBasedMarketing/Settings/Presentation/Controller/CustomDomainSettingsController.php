<?php

namespace App\VideoBasedMarketing\Settings\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\Shared\Presentation\Enum\FlashMessageLabel;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\Membership\Domain\Enum\Capability;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipPlanService;
use App\VideoBasedMarketing\Settings\Domain\Enum\SetCustomDomainNameResult;
use App\VideoBasedMarketing\Settings\Domain\Service\SettingsDomainService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomDomainSettingsController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/settings/custom-domain',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/einstellungen/eigene-domain',
        ],
        name        : 'videobasedmarketing.settings.presentation.custom_domain',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function customDomainAction(
        MembershipPlanService $membershipPlanService,
        CapabilitiesService   $capabilitiesService,
        SettingsDomainService $settingsDomainService
    ): Response
    {
        $user = $this->getUser();

        return $this->render(
            '@videobasedmarketing.settings/custom_domain.html.twig',
            [
                'hasCapability' => $capabilitiesService->canPresentLandingpageOnCustomDomain($user),

                'requiredMembershipPlan' => $membershipPlanService
                    ->getCheapestMembershipPlanRequiredForCapabilities([
                        Capability::CustomDomain
                    ]),

                'customDomainSetting' => $settingsDomainService->getCustomDomainSetting($user)
            ]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/settings/custom-domain/name',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/einstellungen/eigene-domain/name',
        ],
        name        : 'videobasedmarketing.settings.presentation.custom_domain.update_name',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function updateCustomDomainNameAction(
        Request               $request,
        SettingsDomainService $settingsDomainService,
        TranslatorInterface   $translator
    ): Response
    {
        $user = $this->getUser();
        $result = $settingsDomainService->setCustomDomainName(
            $user,
            $request->get('domainName')
        );

        match ($result) {
            SetCustomDomainNameResult::Success => $this->addFlash(
                FlashMessageLabel::Success->value,
                $translator->trans(
                    'custom_domain.flash_message.change_domain_name.success',
                    [],
                    'videobasedmarketing.settings'
                )
            ),

            SetCustomDomainNameResult::InvalidDomainName => $this->addFlash(
                FlashMessageLabel::Danger->value,
                $translator->trans(
                    'custom_domain.flash_message.change_domain_name.invalid_domain_name',
                    [],
                    'videobasedmarketing.settings'
                )
            ),

            SetCustomDomainNameResult::IsApexDomain => $this->addFlash(
                FlashMessageLabel::Warning->value,
                $translator->trans(
                    'custom_domain.flash_message.change_domain_name.is_apex_domain',
                    [],
                    'videobasedmarketing.settings'
                )
            ),

            SetCustomDomainNameResult::IsMercuriusDomain => $this->addFlash(
                FlashMessageLabel::Danger->value,
                $translator->trans(
                    'custom_domain.flash_message.change_domain_name.is_mercurius_domain',
                    [],
                    'videobasedmarketing.settings'
                )
            )
        };

        return $this->redirectToRoute('videobasedmarketing.settings.presentation.custom_domain');
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/settings/custom-domain/namecheck',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/einstellungen/eigene-domain/namensüberprüfung',
        ],
        name        : 'videobasedmarketing.settings.presentation.custom_domain.trigger_domain_name_check',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function triggerDomainNameCheckAction(
        SettingsDomainService $settingsDomainService,
    ): Response
    {
        $settingsDomainService->triggerDomainNameCheck(
            $this->getUser(true)
        );

        return $this->redirectToRoute('videobasedmarketing.settings.presentation.custom_domain');
    }

    #[Route(
        path        : '/settings/custom-domain/verify',
        name        : 'videobasedmarketing.settings.presentation.custom_domain.verify',
        methods     : [Request::METHOD_GET]
    )]
    public function verifyAction(): Response
    {
        return new Response('This custom domain is working.');
    }

    #[Route(
        path        : '/settings/custom-domain/redirect',
        name        : 'videobasedmarketing.settings.presentation.custom_domain.redirect',
        methods     : [Request::METHOD_GET]
    )]
    public function redirectAction(): Response
    {
        return $this->render('@videobasedmarketing.settings/custom_domain_redirect.html.twig');
    }
}
