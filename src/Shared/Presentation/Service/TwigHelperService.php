<?php

namespace App\Shared\Presentation\Service;

use App\Shared\Infrastructure\Service\ContentDeliveryService;
use App\Shared\Infrastructure\Service\CookiesService;
use App\Shared\Infrastructure\Service\ShortIdService;
use App\Shared\Presentation\Entity\NavigationEntry;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Service\AccountDomainService;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\Account\Infrastructure\Service\AccountAssetsService;
use App\VideoBasedMarketing\AudioTranscription\Domain\Service\AudioTranscriptionDomainService;
use App\VideoBasedMarketing\Dashboard\Domain\Service\DashboardDomainService;
use App\VideoBasedMarketing\LingoSync\Domain\Service\LingoSyncDomainService;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipPlanService;
use App\VideoBasedMarketing\Organization\Domain\Service\OrganizationDomainService;
use App\VideoBasedMarketing\Presentationpages\Domain\Service\PresentationpagesService;
use App\VideoBasedMarketing\RecordingRequests\Domain\Service\RecordingRequestsDomainService;
use App\VideoBasedMarketing\Recordings\Domain\Service\RecordingSessionDomainService;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoDomainService;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoFolderDomainService;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use App\VideoBasedMarketing\Recordings\Presentation\Service\EmbeddableVideoPlayerPresentationService;
use App\VideoBasedMarketing\Recordings\Presentation\Service\RecordingsPresentationService;
use App\VideoBasedMarketing\Settings\Domain\Service\SettingsDomainService;
use App\VideoBasedMarketing\Settings\Infrastructure\Service\SettingsInfrastructureService;
use Symfony\Component\HttpFoundation\Request;


class TwigHelperService
{
    private ContentDeliveryService $contentDeliveryService;

    private AccountDomainService $accountDomainService;

    private AccountAssetsService $accountAssetsService;

    private MembershipPlanService $membershipPlanService;

    private CookiesService $cookiesService;

    private VideoDomainService $videoDomainService;

    private VideoFolderDomainService $videoFolderDomainService;

    private RecordingsInfrastructureService $recordingsInfrastructureService;

    private RecordingSessionDomainService $recordingSessionDomainService;

    private RecordingsPresentationService $recordingsPresentationService;

    private EmbeddableVideoPlayerPresentationService $embeddableVideoPlayerPresentationService;

    private ShortIdService $shortIdService;

    private DashboardDomainService $dashboardService;

    private PresentationpagesService $presentationpagesService;

    private RecordingRequestsDomainService $recordingRequestsDomainService;

    private CapabilitiesService $capabilitiesService;

    private SettingsDomainService $settingsDomainService;

    private SettingsInfrastructureService $settingsInfrastructureService;

    private OrganizationDomainService $organizationDomainService;

    private AudioTranscriptionDomainService $audioTranscriptionDomainService;

    private LingoSyncDomainService $lingoSyncDomainService;

    public function __construct(
        ContentDeliveryService                   $contentDeliveryService,
        AccountDomainService                     $accountDomainService,
        AccountAssetsService                     $accountAssetsService,
        MembershipPlanService                        $membershipPlanService,
        CookiesService                           $cookiesService,
        VideoDomainService                       $videoDomainService,
        VideoFolderDomainService                 $videoFolderDomainService,
        RecordingsInfrastructureService          $recordingsInfrastructureService,
        RecordingSessionDomainService            $recordingSessionDomainService,
        RecordingsPresentationService            $recordingsPresentationService,
        EmbeddableVideoPlayerPresentationService $embeddableVideoPlayerPresentationService,
        ShortIdService                           $shortIdService,
        DashboardDomainService                   $dashboardService,
        PresentationpagesService                 $presentationpagesService,
        RecordingRequestsDomainService           $recordingRequestsDomainService,
        CapabilitiesService                      $capabilitiesService,
        SettingsDomainService                    $settingsDomainService,
        SettingsInfrastructureService            $settingsInfrastructureService,
        OrganizationDomainService                $organizationDomainService,
        AudioTranscriptionDomainService          $audioTranscriptionDomainService,
        LingoSyncDomainService                   $lingoSyncDomainService
    )
    {
        $this->contentDeliveryService = $contentDeliveryService;
        $this->accountDomainService = $accountDomainService;
        $this->accountAssetsService = $accountAssetsService;
        $this->membershipPlanService = $membershipPlanService;
        $this->cookiesService = $cookiesService;
        $this->videoDomainService = $videoDomainService;
        $this->videoFolderDomainService = $videoFolderDomainService;
        $this->recordingsInfrastructureService = $recordingsInfrastructureService;
        $this->recordingSessionDomainService = $recordingSessionDomainService;
        $this->recordingsPresentationService = $recordingsPresentationService;
        $this->embeddableVideoPlayerPresentationService = $embeddableVideoPlayerPresentationService;
        $this->shortIdService = $shortIdService;
        $this->dashboardService = $dashboardService;
        $this->presentationpagesService = $presentationpagesService;
        $this->recordingRequestsDomainService = $recordingRequestsDomainService;
        $this->capabilitiesService = $capabilitiesService;
        $this->settingsDomainService = $settingsDomainService;
        $this->settingsInfrastructureService = $settingsInfrastructureService;
        $this->organizationDomainService = $organizationDomainService;
        $this->audioTranscriptionDomainService = $audioTranscriptionDomainService;
        $this->lingoSyncDomainService = $lingoSyncDomainService;
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

    public function getMembershipPlanService(): MembershipPlanService
    {
        return $this->membershipPlanService;
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

    public function getEmbeddableVideoPlayerPresentationService(): EmbeddableVideoPlayerPresentationService
    {
        return $this->embeddableVideoPlayerPresentationService;
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

    public function getAudioTranscriptionDomainService(): AudioTranscriptionDomainService
    {
        return $this->audioTranscriptionDomainService;
    }

    public function getLingoSyncDomainService(): LingoSyncDomainService
    {
        return $this->lingoSyncDomainService;
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

    public function navigationEntryIsActive(
        string $currentRoute,
        NavigationEntry $navigationEntry
    ): bool
    {
        if ($this->routeStartsWith($currentRoute, $navigationEntry->getRouteName())) {
            return true;
        }

        foreach ($navigationEntry->getAdditionalRouteNames() as $additionalRouteName) {
            if ($this->routeStartsWith($currentRoute, $additionalRouteName)) {
                return true;
            }
        }

        return false;
    }

    /** @return NavigationEntry[] */
    public function getSidenavEntries(
        User $user
    ): array
    {
        $result = [
            new NavigationEntry(
                'sidenav.recordings',
                'videobasedmarketing.recordings.presentation.videos.overview',
                ['videobasedmarketing.recordings.presentation.'],
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M1.5 5.625c0-1.036.84-1.875 1.875-1.875h17.25c1.035 0 1.875.84 1.875 1.875v12.75c0 1.035-.84 1.875-1.875 1.875H3.375A1.875 1.875 0 011.5 18.375V5.625zm1.5 0v1.5c0 .207.168.375.375.375h1.5a.375.375 0 00.375-.375v-1.5a.375.375 0 00-.375-.375h-1.5A.375.375 0 003 5.625zm16.125-.375a.375.375 0 00-.375.375v1.5c0 .207.168.375.375.375h1.5A.375.375 0 0021 7.125v-1.5a.375.375 0 00-.375-.375h-1.5zM21 9.375A.375.375 0 0020.625 9h-1.5a.375.375 0 00-.375.375v1.5c0 .207.168.375.375.375h1.5a.375.375 0 00.375-.375v-1.5zm0 3.75a.375.375 0 00-.375-.375h-1.5a.375.375 0 00-.375.375v1.5c0 .207.168.375.375.375h1.5a.375.375 0 00.375-.375v-1.5zm0 3.75a.375.375 0 00-.375-.375h-1.5a.375.375 0 00-.375.375v1.5c0 .207.168.375.375.375h1.5a.375.375 0 00.375-.375v-1.5zM4.875 18.75a.375.375 0 00.375-.375v-1.5a.375.375 0 00-.375-.375h-1.5a.375.375 0 00-.375.375v1.5c0 .207.168.375.375.375h1.5zM3.375 15h1.5a.375.375 0 00.375-.375v-1.5a.375.375 0 00-.375-.375h-1.5a.375.375 0 00-.375.375v1.5c0 .207.168.375.375.375zm0-3.75h1.5a.375.375 0 00.375-.375v-1.5A.375.375 0 004.875 9h-1.5A.375.375 0 003 9.375v1.5c0 .207.168.375.375.375zm4.125 0a.75.75 0 000 1.5h9a.75.75 0 000-1.5h-9z" clip-rule="evenodd" /></svg>'
            )
        ];

        if ($this->capabilitiesService->canShowRecordingRequests($user)) {
            $result[] = new NavigationEntry(
                'sidenav.recording_requests',
                'videobasedmarketing.recording_requests.recording_requests_overview',
                ['videobasedmarketing.recording_requests.'],
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm11.378-3.917c-.89-.777-2.366-.777-3.255 0a.75.75 0 01-.988-1.129c1.454-1.272 3.776-1.272 5.23 0 1.513 1.324 1.513 3.518 0 4.842a3.75 3.75 0 01-.837.552c-.676.328-1.028.774-1.028 1.152v.75a.75.75 0 01-1.5 0v-.75c0-1.279 1.06-2.107 1.875-2.502.182-.088.351-.199.503-.331.83-.727.83-1.857 0-2.584zM12 18a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" /></svg>'
            );
        }

        $result[] = new NavigationEntry(
            'sidenav.organization',
            'videobasedmarketing.organization.overview',
            ['videobasedmarketing.organization.'],
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0zM15.75 9.75a3 3 0 116 0 3 3 0 01-6 0zM2.25 9.75a3 3 0 116 0 3 3 0 01-6 0zM6.31 15.117A6.745 6.745 0 0112 12a6.745 6.745 0 016.709 7.498.75.75 0 01-.372.568A12.696 12.696 0 0112 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 01-.372-.568 6.787 6.787 0 011.019-4.38z" clip-rule="evenodd" /><path d="M5.082 14.254a8.287 8.287 0 00-1.308 5.135 9.687 9.687 0 01-1.764-.44l-.115-.04a.563.563 0 01-.373-.487l-.01-.121a3.75 3.75 0 013.57-4.047zM20.226 19.389a8.287 8.287 0 00-1.308-5.135 3.75 3.75 0 013.57 4.047l-.01.121a.563.563 0 01-.373.486l-.115.04c-.567.2-1.156.349-1.764.441z" /></svg>'
        );

        if ($this->capabilitiesService->canEditCustomLogoSetting($user)) {
            $result[] = new NavigationEntry(
                'sidenav.settings_custom_logo',
                'videobasedmarketing.settings.presentation.custom_logo',
                [],
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M1.5 6a2.25 2.25 0 012.25-2.25h16.5A2.25 2.25 0 0122.5 6v12a2.25 2.25 0 01-2.25 2.25H3.75A2.25 2.25 0 011.5 18V6zM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0021 18v-1.94l-2.69-2.689a1.5 1.5 0 00-2.12 0l-.88.879.97.97a.75.75 0 11-1.06 1.06l-5.16-5.159a1.5 1.5 0 00-2.12 0L3 16.061zm10.125-7.81a1.125 1.125 0 112.25 0 1.125 1.125 0 01-2.25 0z" clip-rule="evenodd" /></svg>'
            );
        }
        
        if ($this->capabilitiesService->canEditCustomDomainSetting($user)) {
            $result[] = new NavigationEntry(
                'sidenav.settings_custom_domain',
                'videobasedmarketing.settings.presentation.custom_domain',
                [],
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M21.721 12.752a9.711 9.711 0 00-.945-5.003 12.754 12.754 0 01-4.339 2.708 18.991 18.991 0 01-.214 4.772 17.165 17.165 0 005.498-2.477zM14.634 15.55a17.324 17.324 0 00.332-4.647c-.952.227-1.945.347-2.966.347-1.021 0-2.014-.12-2.966-.347a17.515 17.515 0 00.332 4.647 17.385 17.385 0 005.268 0zM9.772 17.119a18.963 18.963 0 004.456 0A17.182 17.182 0 0112 21.724a17.18 17.18 0 01-2.228-4.605zM7.777 15.23a18.87 18.87 0 01-.214-4.774 12.753 12.753 0 01-4.34-2.708 9.711 9.711 0 00-.944 5.004 17.165 17.165 0 005.498 2.477zM21.356 14.752a9.765 9.765 0 01-7.478 6.817 18.64 18.64 0 001.988-4.718 18.627 18.627 0 005.49-2.098zM2.644 14.752c1.682.971 3.53 1.688 5.49 2.099a18.64 18.64 0 001.988 4.718 9.765 9.765 0 01-7.478-6.816zM13.878 2.43a9.755 9.755 0 016.116 3.986 11.267 11.267 0 01-3.746 2.504 18.63 18.63 0 00-2.37-6.49zM12 2.276a17.152 17.152 0 012.805 7.121c-.897.23-1.837.353-2.805.353-.968 0-1.908-.122-2.805-.353A17.151 17.151 0 0112 2.276zM10.122 2.43a18.629 18.629 0 00-2.37 6.49 11.266 11.266 0 01-3.746-2.504 9.754 9.754 0 016.116-3.985z" /></svg>'
            );
        }
        
        return $result;
    }
}
