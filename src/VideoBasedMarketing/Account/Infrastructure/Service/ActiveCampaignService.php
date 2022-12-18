<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\Service;


use App\VideoBasedMarketing\Account\Domain\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ActiveCampaignService
{
    private HttpClientInterface $httpClient;

    private string $apiUrl = 'https://api.activecampaign.com/api/3/';

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function createContact(User $user): void
    {
        $response = $this->client->request(
            Request::METHOD_POST,
            'https://api.activecampaign.com/api/3/contacts',
            [
                'headers' => [
}
