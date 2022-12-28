<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\Controller;

use App\VideoBasedMarketing\Account\Infrastructure\Service\ThirdPartyAuthService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\LinkedInClient;
use League\OAuth2\Client\Provider\LinkedInResourceOwner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;


class ThirdPartyAuthController
    extends AbstractController
{
    #[Route(
        path        : 'account/thirdpartyauth/linkedin/start',
        name        : 'videobasedmarketing.account.infrastructure.thirdpartyauth.linkedin.start',
        methods     : [Request::METHOD_GET, Request::METHOD_POST]
    )]
    public function linkedinStartAction(
        ClientRegistry $clientRegistry
    ): Response
    {
        return $clientRegistry
            ->getClient('linkedin')
            ->redirect(
                [
                    'r_liteprofile', 'r_emailaddress'
                ]
            );
    }

    #[Route(
        path        : 'account/thirdpartyauth/linkedin/return',
        name        : 'videobasedmarketing.account.infrastructure.thirdpartyauth.linkedin.return',
        methods     : [Request::METHOD_GET, Request::METHOD_POST]
    )]
    public function linkedinReturnAction(
        ClientRegistry        $clientRegistry,
        ThirdPartyAuthService $thirdPartyAuthService
    ): Response
    {
        try {
            /** @var LinkedInClient $client */
            $client = $clientRegistry->getClient('linkedin');

            /** @var LinkedInResourceOwner $receivedResourceOwner */
            $receivedResourceOwner = $client->fetchUser();

            $result = $thirdPartyAuthService
                ->handleReceivedLinkedInResourceOwner($receivedResourceOwner);

            if (!$result->wasSuccessful()) {

                // TODO: Redirect to a presentation route
                return $this->render(
                    '@videobasedmarketing.account/thirdpartyauth/error.html.twig',
                    [
                        'error' => $result->getError(),
                        'gotThrowable' => false
                    ],
                    new Response(null, Response::HTTP_INTERNAL_SERVER_ERROR)
                );
            }

            if (!is_null($result->getLoginLinkUrl())) {
                return $this->redirect($result->getLoginLinkUrl());
            }

            return new Response(print_r($receivedResourceOwner, true));
        } catch (Throwable $t) {

            // TODO: Redirect to a presentation route
            return $this->render(
                '@videobasedmarketing.account/thirdpartyauth/error.html.twig',
                [
                    'error' => -1,
                    'gotThrowable' => true,
                    'throwable' => $t
                ],
                new Response(null, Response::HTTP_INTERNAL_SERVER_ERROR)
            );
        }
    }
}
