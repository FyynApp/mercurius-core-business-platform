<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\Service;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class HappyScribeApiService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private HttpClientInterface    $httpClient
    )
    {
    }

    public function createTranscription(

    )
    {

    }
}
