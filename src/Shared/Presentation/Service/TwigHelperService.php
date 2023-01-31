<?php

namespace App\Shared\Presentation\Service;

use App\Shared\Infrastructure\Service\ContentDeliveryService;
use App\Shared\Infrastructure\Service\CookiesService;
use App\Shared\Infrastructure\Service\ShortIdService;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\Account\Infrastructure\Service\AccountAssetsService;
use App\VideoBasedMarketing\Dashboard\Domain\Service\DashboardDomainService;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipService;
use App\VideoBasedMarketing\Presentationpages\Domain\Service\PresentationpagesService;
use App\VideoBasedMarketing\RecordingRequests\Domain\Service\RecordingRequestsDomainService;
use App\VideoBasedMarketing\Recordings\Domain\Service\RecordingSessionDomainService;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoDomainService;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use App\VideoBasedMarketing\Recordings\Presentation\Service\RecordingsPresentationService;
use App\VideoBasedMarketing\Settings\Domain\Service\SettingsDomainService;
use App\VideoBasedMarketing\Settings\Infrastructure\Service\SettingsInfrastructureService;


class TwigHelperService
{
    private ContentDeliveryService $contentDeliveryService;

    private AccountAssetsService $accountAssetsService;

    private MembershipService $membershipService;

    private CookiesService $cookiesService;

    private VideoDomainService $videoDomainService;

    private RecordingsInfrastructureService $recordingsInfrastructureService;

    private RecordingSessionDomainService $recordingSessionDomainService;

    private RecordingsPresentationService $recordingsPresentationService;

    private ShortIdService $shortIdService;

    private DashboardDomainService $dashboardService;

    private PresentationpagesService $presentationpagesService;

    private RecordingRequestsDomainService $recordingRequestsDomainService;

    private CapabilitiesService $capabilitiesService;

    private SettingsDomainService $settingsDomainService;

    private SettingsInfrastructureService $settingsInfrastructureService;


    public function __construct(
        ContentDeliveryService          $contentDeliveryService,
        AccountAssetsService            $accountAssetsService,
        MembershipService               $membershipService,
        CookiesService                  $cookiesService,
        VideoDomainService              $videoDomainService,
        RecordingsInfrastructureService $recordingsInfrastructureService,
        RecordingSessionDomainService   $recordingSessionDomainService,
        RecordingsPresentationService   $recordingsPresentationService,
        ShortIdService                  $shortIdService,
        DashboardDomainService          $dashboardService,
        PresentationpagesService        $presentationpagesService,
        RecordingRequestsDomainService  $recordingRequestsDomainService,
        CapabilitiesService             $capabilitiesService,
        SettingsDomainService           $settingsDomainService,
        SettingsInfrastructureService   $settingsInfrastructureService
    )
    {
        $this->contentDeliveryService = $contentDeliveryService;
        $this->accountAssetsService = $accountAssetsService;
        $this->membershipService = $membershipService;
        $this->cookiesService = $cookiesService;
        $this->videoDomainService = $videoDomainService;
        $this->recordingsInfrastructureService = $recordingsInfrastructureService;
        $this->recordingSessionDomainService = $recordingSessionDomainService;
        $this->recordingsPresentationService = $recordingsPresentationService;
        $this->shortIdService = $shortIdService;
        $this->dashboardService = $dashboardService;
        $this->presentationpagesService = $presentationpagesService;
        $this->recordingRequestsDomainService = $recordingRequestsDomainService;
        $this->capabilitiesService = $capabilitiesService;
        $this->settingsDomainService = $settingsDomainService;
        $this->settingsInfrastructureService = $settingsInfrastructureService;
    }

    public function getSymfonyEnv(): string
    {
        return $_ENV['APP_ENV'];
    }

    public function getContentDeliveryService(): ContentDeliveryService
    {
        return $this->contentDeliveryService;
    }

    public function getAccountAssetsService(): AccountAssetsService
    {
        return $this->accountAssetsService;
    }

    public function getMembershipService(): MembershipService
    {
        return $this->membershipService;
    }

    public function getCookiesService(): CookiesService
    {
        return $this->cookiesService;
    }

    public function getVideoDomainService(): VideoDomainService
    {
        return $this->videoDomainService;
    }

    public function getRecordingsInfrastructureService(): RecordingsInfrastructureService
    {
        return $this->recordingsInfrastructureService;
    }

    public function getRecordingSessionDomainService(): RecordingSessionDomainService
    {
        return $this->recordingSessionDomainService;
    }

    public function getRecordingsPresentationService(): RecordingsPresentationService
    {
        return $this->recordingsPresentationService;
    }

    public function getShortIdService(): ShortIdService
    {
        return $this->shortIdService;
    }

    public function getDashboardService(): DashboardDomainService
    {
        return $this->dashboardService;
    }

    public function getPresentationpagesService(): PresentationpagesService
    {
        return $this->presentationpagesService;
    }

    public function getRecordingRequestsDomainService(): RecordingRequestsDomainService
    {
        return $this->recordingRequestsDomainService;
    }

    public function getCapabilitiesService(): CapabilitiesService
    {
        return $this->capabilitiesService;
    }

    public function getSettingsDomainService(): SettingsDomainService
    {
        return $this->settingsDomainService;
    }

    public function getSettingsInfrastructureService(): SettingsInfrastructureService
    {
        return $this->settingsInfrastructureService;
    }
}
