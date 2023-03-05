<?php

namespace App\Shared\Presentation\Service;

use App\Shared\Infrastructure\Service\ContentDeliveryService;
use App\Shared\Infrastructure\Service\CookiesService;
use App\Shared\Infrastructure\Service\ShortIdService;
use App\VideoBasedMarketing\Account\Domain\Service\AccountDomainService;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\Account\Infrastructure\Service\AccountAssetsService;
use App\VideoBasedMarketing\Dashboard\Domain\Service\DashboardDomainService;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipService;
use App\VideoBasedMarketing\Organization\Domain\Service\OrganizationDomainService;
use App\VideoBasedMarketing\Presentationpages\Domain\Service\PresentationpagesService;
use App\VideoBasedMarketing\RecordingRequests\Domain\Service\RecordingRequestsDomainService;
use App\VideoBasedMarketing\Recordings\Domain\Service\RecordingSessionDomainService;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoDomainService;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoFolderDomainService;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use App\VideoBasedMarketing\Recordings\Presentation\Service\RecordingsPresentationService;
use App\VideoBasedMarketing\Settings\Domain\Service\SettingsDomainService;
use App\VideoBasedMarketing\Settings\Infrastructure\Service\SettingsInfrastructureService;
use Symfony\Component\HttpFoundation\Request;


class TwigHelperService
{
    private ContentDeliveryService $contentDeliveryService;

    private AccountDomainService $accountDomainService;

    private AccountAssetsService $accountAssetsService;

    private MembershipService $membershipService;

    private CookiesService $cookiesService;

    private VideoDomainService $videoDomainService;

    private VideoFolderDomainService $videoFolderDomainService;

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

    private OrganizationDomainService $organizationDomainService;

    public function __construct(
        ContentDeliveryService          $contentDeliveryService,
        AccountDomainService            $accountDomainService,
        AccountAssetsService            $accountAssetsService,
        MembershipService               $membershipService,
        CookiesService                  $cookiesService,
        VideoDomainService              $videoDomainService,
        VideoFolderDomainService        $videoFolderDomainService,
        RecordingsInfrastructureService $recordingsInfrastructureService,
        RecordingSessionDomainService   $recordingSessionDomainService,
        RecordingsPresentationService   $recordingsPresentationService,
        ShortIdService                  $shortIdService,
        DashboardDomainService          $dashboardService,
        PresentationpagesService        $presentationpagesService,
        RecordingRequestsDomainService  $recordingRequestsDomainService,
        CapabilitiesService             $capabilitiesService,
        SettingsDomainService           $settingsDomainService,
        SettingsInfrastructureService   $settingsInfrastructureService,
        OrganizationDomainService       $organizationDomainService
    )
    {
        $this->contentDeliveryService = $contentDeliveryService;
        $this->accountDomainService = $accountDomainService;
        $this->accountAssetsService = $accountAssetsService;
        $this->membershipService = $membershipService;
        $this->cookiesService = $cookiesService;
        $this->videoDomainService = $videoDomainService;
        $this->videoFolderDomainService = $videoFolderDomainService;
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
        $this->organizationDomainService = $organizationDomainService;
    }

    public function getSymfonyEnv(): string
    {
        return $_ENV['APP_ENV'];
    }

    public function getEnvVar(string $envVar): string
    {
        return $_ENV[$envVar];
    }

    public function getContentDeliveryService(): ContentDeliveryService
    {
        return $this->contentDeliveryService;
    }

    public function getAccountDomainService(): AccountDomainService
    {
        return $this->accountDomainService;
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

    public function getVideoFolderDomainService(): VideoFolderDomainService
    {
        return $this->videoFolderDomainService;
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

    public function getOrganizationDomainService(): OrganizationDomainService
    {
        return $this->organizationDomainService;
    }

    public function routeStartsWith(
        string $route,
        string $startsWith
    ): bool
    {
        return str_starts_with(
            $route,
            $startsWith
        );
    }
}
