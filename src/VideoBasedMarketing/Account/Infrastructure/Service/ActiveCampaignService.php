<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Infrastructure\Entity\ActiveCampaignContact;
use App\VideoBasedMarketing\Account\Infrastructure\Enum\ActiveCampaignContactTag;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;


readonly class ActiveCampaignService
{
    private HttpClientInterface $httpClient;

    private ContainerBagInterface $containerBag;

    private EntityManagerInterface $entityManager;

    private string $apiUrl;

    private string $apiToken;

    public function __construct(
        HttpClientInterface    $httpClient,
        ContainerBagInterface  $containerBag,
        EntityManagerInterface $entityManager
    )
    {
        $this->httpClient = $httpClient;
        $this->containerBag = $containerBag;
        $this->entityManager = $entityManager;

        $this->apiUrl = $this->containerBag->get('app.activecampaign.api_url');
        $this->apiToken = $this->containerBag->get('app.activecampaign.api_token');
    }

    public function userHasContact(User $user): bool
    {
        return !is_null($user->getActiveCampaignContact());
    }

    /**
     * @throws Exception|TransportExceptionInterface
     */
    public function createContact(User $user): ActiveCampaignContact
    {
        if ($this->userHasContact($user)) {
            throw new Exception(
                "User already has contact '{$user->getActiveCampaignContact()->getId()}' at ActiveCampaign."
            );
        }

        $response = $this->httpClient->request(
            Request::METHOD_POST,
            "$this->apiUrl/contacts",
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                    'Api-Token'    => $this->apiToken
                ],
                'body' => json_encode([
                    'contact' => [
                        'email' => $user->getEmail()
                    ]
                ]),
            ]
        );

        if ($response->getStatusCode() !== Response::HTTP_CREATED) {
            throw new Exception(
                "Instead of status code '" . Response::HTTP_CREATED . "', ActiveCampaign returned status code '{$response->getStatusCode()}' with message '{$response->getContent()}'"
            );
        }

        $content = $response->getContent();

        $content = json_decode($content, true);

        if (!array_key_exists('contact', $content)) {
            throw new Exception("No 'contact' field in response.");
        }

        if (!array_key_exists('id', $content['contact'])) {
            throw new Exception("No 'contact[id]' field in response.");
        }

        if (!is_numeric($content['contact']['id'])) {
            throw new Exception("Field 'contact[id]' is not numeric.");
        }

        $contact = new ActiveCampaignContact(
            (int)$content['contact']['id'],
            $user
        );

        $this->entityManager->persist($contact);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $contact;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function addTagToContact(
        ActiveCampaignContact    $contact,
        ActiveCampaignContactTag $tag
    ): void
    {
        $this->httpClient->request(
            Request::METHOD_POST,
            "$this->apiUrl/contactTags",
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                    'Api-Token'    => $this->apiToken
                ],
                'body' => json_encode([
                    'contactTag' => [
                        'contact' => $contact->getId(),
                        'tag'     => $tag->value
                    ]
                ]),
            ]
        );
    }
}
