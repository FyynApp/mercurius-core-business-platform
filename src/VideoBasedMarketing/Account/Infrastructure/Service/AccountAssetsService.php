<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\Service;

use App\Shared\ContentDelivery\Infrastructure\Service\ContentDeliveryService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;


class AccountAssetsService
{
    private ContentDeliveryService $contentDeliveryService;

    public function __construct(
        ContentDeliveryService $contentDeliveryService
    )
    {
        $this->contentDeliveryService = $contentDeliveryService;
    }


    public function getUrlForUserProfilePhoto(User $user): ?string
    {
        if ($user->hasProfilePhoto()) {
            $url = $user->getProfilePhotoUrl();

            if (is_null($url)) {
                return null;
            }

            if (   mb_substr($url, 0, 7) === 'http://'
                || mb_substr($url, 0, 8) === 'https://'
            ) {
                return $this
                    ->contentDeliveryService
                    ->getServeUrlForExternalAsset(
                        $url,
                        $user->getProfilePhotoContentType()
                );
            }
        }

        return null;
    }
}
