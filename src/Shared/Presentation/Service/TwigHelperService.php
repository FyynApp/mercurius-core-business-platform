<?php

namespace App\Shared\Presentation\Service;

use App\Shared\Infrastructure\Service\ContentDeliveryService;
use App\Shared\Infrastructure\Service\CookiesService;
use App\Shared\Infrastructure\Service\ShortIdService;
use App\VideoBasedMarketing\Account\Infrastructure\Service\AccountAssetsService;
use App\VideoBasedMarketing\Dashboard\Domain\Service\DashboardService;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipService;
use App\VideoBasedMarketing\Presentationpages\Domain\Service\PresentationpagesService;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoService;


class TwigHelperService
{
    private ContentDeliveryService $contentDeliveryService;

    private AccountAssetsService $accountAssetsService;

    private MembershipService $membershipService;

    private CookiesService $cookiesService;

    private VideoService $videoService;

    private ShortIdService $shortIdService;

    private DashboardService $dashboardService;

    private PresentationpagesService $presentationpagesService;

    public function __construct(
        ContentDeliveryService                            $contentDeliveryService,
        AccountAssetsService                              $accountAssetsService,
        MembershipService                                 $membershipService,
        CookiesService $cookiesService,
        VideoService                                      $videoService,
        ShortIdService                                    $shortIdService,
        DashboardService                                  $dashboardService,
        PresentationpagesService                          $presentationpagesService
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
