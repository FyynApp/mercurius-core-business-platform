<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\Service;


use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscription;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Entity\HappyScribeExport;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Entity\HappyScribeTranscription;
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
        AudioTranscription $audioTranscription
    ): HappyScribeTranscription
    {
        return new HappyScribeTranscription();
    }

    public function updateTranscription(
        HappyScribeTranscription $happyScribeTranscription
    ): void
    {

    }

    public function createExport(
        HappyScribeTranscription $happyScribeTranscription
    ): HappyScribeExport
    {
        return new HappyScribeExport();
    }
}
