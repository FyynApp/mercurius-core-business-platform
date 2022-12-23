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


class TwigHelperService
{
    private ContentDeliveryService $contentDeliveryService;

    private AccountAssetsService $accountAssetsService;

    private MembershipService $membershipService;

    private CookiesService $cookiesService;

    private VideoDomainService $videoDomainService;

    private RecordingsInfrastructureService $recordingsInfrastructureService;

    private RecordingSessionDomainService $recordingSessionDomainService;

    private ShortIdService $shortIdService;

    private DashboardDomainService $dashboardService;

    private PresentationpagesService $presentationpagesService;

    private RecordingRequestsDomainService $recordingRequestsDomainService;

    private CapabilitiesService $capabilitiesService;


    public function __construct(
        ContentDeliveryService          $contentDeliveryService,
        AccountAssetsService            $accountAssetsService,
        MembershipService               $membershipService,
        CookiesService                  $cookiesService,
        VideoDomainService              $videoDomainService,
        RecordingsInfrastructureService $recordingsInfrastructureService,
        RecordingSessionDomainService   $recordingSessionDomainService,
        ShortIdService                  $shortIdService,
        DashboardDomainService          $dashboardService,
        PresentationpagesService        $presentationpagesService,
        RecordingRequestsDomainService  $recordingRequestsDomainService,
        CapabilitiesService             $capabilitiesService
    )
    {
        $this->contentDeliveryService = $contentDeliveryService;
        $this->accountAssetsService = $accountAssetsService;
        $this->membershipService = $membershipService;
        $this->cookiesService = $cookiesService;
        $this->videoDomainService = $videoDomainService;
        $this->recordingsInfrastructureService = $recordingsInfrastructureService;
        $this->recordingSessionDomainService = $recordingSessionDomainService;
        $this->shortIdService = $shortIdService;
        $this->dashboardService = $dashboardService;
        $this->presentationpagesService = $presentationpagesService;
        $this->recordingRequestsDomainService = $recordingRequestsDomainService;
        $this->capabilitiesService = $capabilitiesService;
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
}
