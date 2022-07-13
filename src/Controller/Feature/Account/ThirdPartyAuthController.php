<?php

namespace App\Controller\Feature\Account;

use App\Service\Feature\Account\ThirdPartyAuthService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\LinkedInClient;
use League\OAuth2\Client\Provider\LinkedInResourceOwner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ThirdPartyAuthController extends AbstractController
{
    public function linkedinStartAction(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('linkedin')
            ->redirect([
                'r_liteprofile', 'r_emailaddress'
            ]);
    }

    public function linkedinReturnAction(ClientRegistry $clientRegistry, ThirdPartyAuthService $thirdPartyAuthService): Response
    {
        try {
            /** @var LinkedInClient $client */
            $client = $clientRegistry->getClient('linkedin');

            /** @var LinkedInResourceOwner $receivedResourceOwner */
            $receivedResourceOwner = $client->fetchUser();

            $result = $thirdPartyAuthService->handleReceivedLinkedInResourceOwner($receivedResourceOwner);

            if (!$result->wasSuccessful()) {
                return $this->render(
                    'feature/account/thirdpartyauth/error.html.twig',
                    [],
                    new Response(null, Response::HTTP_INTERNAL_SERVER_ERROR)
                );
            }

            return new Response(print_r($receivedResourceOwner, true));
        } catch (Throwable $t) {
            return $this->render(
                'feature/account/thirdpartyauth/error.html.twig',
                [],
                new Response(null, Response::HTTP_INTERNAL_SERVER_ERROR)
            );
        }
    }
}
