<?php

namespace App\Shared\Twig\Presentation\Service;

use App\Service\Feature\Dashboard\DashboardService;
use App\Service\Feature\Presentationpages\PresentationpagesService;
use App\Service\Feature\Recordings\VideoService;
use App\Shared\Cookies\Infrastructure\Service\CookiesService;
use App\Shared\Entities\Infrastructure\Service\ShortIdService;
use App\Shared\Infrastructure\Service\ContentDeliveryService;
use App\VideoBasedMarketing\Account\Infrastructure\Service\AccountAssetsService;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipService;


class TwigHelperService
{
    private \App\Shared\Infrastructure\Service\ContentDeliveryService $contentDeliveryService;

    private AccountAssetsService $accountAssetsService;

    private MembershipService $membershipService;

    private CookiesService $cookiesService;

    private VideoService $videoService;

    private ShortIdService $shortIdService;

    private DashboardService $dashboardService;

    private PresentationpagesService $presentationpagesService;

    public function __construct(
        ContentDeliveryService   $contentDeliveryService,
        AccountAssetsService     $accountAssetsService,
        MembershipService        $membershipService,
        CookiesService           $cookiesService,
        VideoService             $videoService,
        ShortIdService           $shortIdService,
        DashboardService         $dashboardService,
        PresentationpagesService $presentationpagesService
    )
    {
        $this->contentDeliveryService = $contentDeliveryService;
        $this->accountAssetsService = $accountAssetsService;
        $this->membershipService = $membershipService;
        $this->cookiesService = $cookiesService;
        $this->videoService = $videoService;
        $this->shortIdService = $shortIdService;
        $this->dashboardService = $dashboardService;
        $this->presentationpagesService = $presentationpagesService;
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

    public function getVideoService(): VideoService
    {
        return $this->videoService;
    }

    public function getShortIdService(): ShortIdService
    {
        return $this->shortIdService;
    }

    public function getDashboardService(): DashboardService
    {
        return $this->dashboardService;
    }

    public function getPresentationpagesService(): PresentationpagesService
    {
        return $this->presentationpagesService;
    }
}
