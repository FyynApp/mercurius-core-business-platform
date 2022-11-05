<?php

namespace App\Shared\Infrastructure\Service;

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
}
