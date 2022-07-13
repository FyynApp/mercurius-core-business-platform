<?php

namespace App\Controller\Aspect\ContentDelivery;

use App\Service\Aspect\ContentDelivery\ContentDeliveryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentDeliveryController extends AbstractController
{
    public function serveExternalAssetAction(Request $request, ContentDeliveryService $contentDeliveryService): Response
    {
        $checksum = $request->get('checksum');
        $externalAssetUrl = $request->get('externalAssetUrl');
        $contentType = $request->get('contentType');
        if ($checksum !== $contentDeliveryService->getChecksumForServingExternalAsset($externalAssetUrl, $contentType)) {
            return new Response('Bad request', Response::HTTP_BAD_REQUEST);
        }

        $assetContent = file_get_contents($externalAssetUrl);

        return new Response(
            $assetContent,
            Response::HTTP_OK,
            ['Content-Type' => $contentType]
        );
    }
}
