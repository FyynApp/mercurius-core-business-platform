<?php

namespace App\Service\Aspect\ContentDelivery;

use App\BoundedContext\Account\Domain\Entity\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;


class ContentDeliveryService
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function getChecksumForServingExternalAsset(
        string $externalAssetUrl,
        string $contentType
    ): string
    {
        return sha1('x87z3n479xn478zxn9x478nxz7845nz' . $externalAssetUrl . $contentType);
    }

    public function getServeUrlForExternalAsset(
        string $externalAssetUrl,
        string $contentType
    ): string
    {
        return $this->router->generate(
            'aspect.content_delivery.serve_external_asset',
            [
                'externalAssetUrl' => $externalAssetUrl,
                'contentType' => $contentType,
                'checksum' => $this->getChecksumForServingExternalAsset($externalAssetUrl, $contentType)
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
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
                return $this->getServeUrlForExternalAsset(
                    $url,
                    $user->getProfilePhotoContentType()
                );
            }
        }

        return null;
    }
}
