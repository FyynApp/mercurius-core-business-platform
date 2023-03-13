<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\Service;


use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscription;
use App\VideoBasedMarketing\AudioTranscription\Domain\Enum\AudioTranscriptionBcp47LanguageCode;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Entity\HappyScribeExport;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Entity\HappyScribeTranscription;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Entity\HappyScribeTranslationTask;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Enum\HappyScribeExportFormat;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Enum\HappyScribeExportState;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Enum\HappyScribeTranscriptionState;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Enum\HappyScribeTranslationTaskState;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

readonly class HappyScribeApiService
{
    private string $apiUrl;

    private string $apiKey;

    private string $happyScribeOrganizationId;

    private string $happyScribeFolderId;

    public function __construct(
        private HttpClientInterface             $httpClient,
        private RecordingsInfrastructureService $recordingsInfrastructureService
    )
    {
        $this->apiUrl = 'https://www.happyscribe.com/api/v1';
        $this->apiKey = getenv('HAPPY_SCRIBE_API_KEY');
        $this->happyScribeOrganizationId = getenv('HAPPY_SCRIBE_ORGANIZATION_ID');
        $this->happyScribeFolderId = getenv('HAPPY_SCRIBE_FOLDER_ID');
    }

    /**
     * @throws Exception
     */
    public function createTranscription(
        AudioTranscription $audioTranscription
    ): HappyScribeTranscription
    {
        $body = <<<EOT
            {
                "transcription": {
                    "name": "{$audioTranscription->getId()}",
                    "language": "{$audioTranscription->getOriginalLanguageBcp47LanguageCode()->value}",
                    "tmp_url": "{$this->recordingsInfrastructureService->getVideoFullAssetUrl($audioTranscription->getVideo())}",
                    "is_subtitle": false,
                    "organization_id": "$this->happyScribeOrganizationId",
                    "folder_id": "$this->happyScribeFolderId"
                }
            }
        EOT;
        $body = trim($body);

        try {
            $response = $this->httpClient->request(
                Request::METHOD_POST,
                "$this->apiUrl/transcriptions",
                [
                    'headers' => [
                        'Content-Type'  => 'application/json',
                        'Accept'        => 'application/json',
                        'Authorization' => "Bearer $this->apiKey"
                    ],
                    'body' => $body,
                ]
            );

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                throw new Exception(
                    "Instead of status code '" . Response::HTTP_OK . "', HappyScribe returned status code '{$response->getStatusCode()}' with message '{$response->getContent()}'"
                );
            }

            $content = $response->getContent();

            $content = json_decode($content, true);

            return new HappyScribeTranscription(
                $audioTranscription,
                $content['id'],
                HappyScribeTranscriptionState::from($content['state']),
                $audioTranscription->getOriginalLanguageBcp47LanguageCode()
            );
        } catch (Throwable $throwable) {
            throw new Exception('', null, $throwable);
        }
    }

    /**
     * @throws Exception
     */
    public function updateTranscription(
        HappyScribeTranscription $happyScribeTranscription
    ): void
    {
        try {
            $response = $this->httpClient->request(
                Request::METHOD_GET,
                "$this->apiUrl/transcriptions/{$happyScribeTranscription->getId()}",
                [
                    'headers' => [
                        'Content-Type'  => 'application/json',
                        'Accept'        => 'application/json',
                        'Authorization' => "Bearer $this->apiKey"
                    ]
                ]
            );

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                throw new Exception(
                    "Instead of status code '" . Response::HTTP_OK . "', HappyScribe returned status code '{$response->getStatusCode()}' with message '{$response->getContent()}'"
                );
            }

            $content = $response->getContent();

            $content = json_decode($content, true);

            $happyScribeTranscription->setState(
                HappyScribeTranscriptionState::from($content['state'])
            );

        } catch (Throwable $throwable) {
            throw new Exception('', null, $throwable);
        }
    }


    /**
     * @throws Exception
     */
    public function createExport(
        HappyScribeTranscription $happyScribeTranscription,
        HappyScribeExportFormat  $exportFormat
    ): HappyScribeExport
    {
        $body = <<<EOT
            {
                "export": {
                    "format": "$exportFormat->value",
                    "transcription_ids": [
                        "{$happyScribeTranscription->getId()}"
                  ]
                }
            }
        EOT;
        $body = trim($body);

        try {
            $response = $this->httpClient->request(
                Request::METHOD_POST,
                "$this->apiUrl/exports",
                [
                    'headers' => [
                        'Content-Type'  => 'application/json',
                        'Accept'        => 'application/json',
                        'Authorization' => "Bearer $this->apiKey"
                    ],
                    'body' => $body,
                ]
            );

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                throw new Exception(
                    "Instead of status code '" . Response::HTTP_OK . "', HappyScribe returned status code '{$response->getStatusCode()}' with message '{$response->getContent()}'"
                );
            }

            $content = $response->getContent();

            $content = json_decode($content, true);

            return new HappyScribeExport(
                $happyScribeTranscription,
                $content['id'],
                HappyScribeExportState::from($content['state']),
                $exportFormat
            );
        } catch (Throwable $throwable) {
            throw new Exception('', null, $throwable);
        }
    }

    /**
     * @throws Exception
     */
    public function updateExport(
        HappyScribeExport $happyScribeExport
    ): void
    {
        try {
            $response = $this->httpClient->request(
                Request::METHOD_GET,
                "$this->apiUrl/exports/{$happyScribeExport->getId()}",
                [
                    'headers' => [
                        'Content-Type'  => 'application/json',
                        'Accept'        => 'application/json',
                        'Authorization' => "Bearer $this->apiKey"
                    ]
                ]
            );

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                throw new Exception(
                    "Instead of status code '" . Response::HTTP_OK . "', HappyScribe returned status code '{$response->getStatusCode()}' with message '{$response->getContent()}'"
                );
            }

            $content = $response->getContent();

            $content = json_decode($content, true);

            $happyScribeExport->setState(
                HappyScribeExportState::from($content['state'])
            );

            if (array_key_exists('download_link', $content)) {
                $happyScribeExport->setDownloadLink($content['download_link']);
            }

        } catch (Throwable $throwable) {
            throw new Exception('', null, $throwable);
        }
    }


    /**
     * @throws Exception
     */
    public function createTranslationTask(
        HappyScribeTranscription $happyScribeTranscription,
        AudioTranscriptionBcp47LanguageCode $audioTranscriptionBcp47LanguageCode
    ): HappyScribeTranslationTask
    {
        if ($audioTranscriptionBcp47LanguageCode === AudioTranscriptionBcp47LanguageCode::DeDe) {
            $targetLanguage = 'en';
        } elseif ($audioTranscriptionBcp47LanguageCode === AudioTranscriptionBcp47LanguageCode::EnUs) {
            $targetLanguage = 'de';
        } else {
            throw new Exception("Unexpected language code '$audioTranscriptionBcp47LanguageCode->value'.");
        }

        $body = <<<EOT
            {
                "source_transcription_id": "{$happyScribeTranscription->getId()}",
                "target_language": "$targetLanguage",
            }
        EOT;
        $body = trim($body);

        try {
            $response = $this->httpClient->request(
                Request::METHOD_POST,
                "$this->apiUrl/task/transcription_translation",
                [
                    'headers' => [
                        'Content-Type'  => 'application/json',
                        'Accept'        => 'application/json',
                        'Authorization' => "Bearer $this->apiKey"
                    ],
                    'body' => $body,
                ]
            );

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                throw new Exception(
                    "Instead of status code '" . Response::HTTP_OK . "', HappyScribe returned status code '{$response->getStatusCode()}' with message '{$response->getContent()}'"
                );
            }

            $content = $response->getContent();

            $content = json_decode($content, true);

            return new HappyScribeTranslationTask(
                $happyScribeTranscription,
                $content['id'],
                HappyScribeTranslationTaskState::from($content['state']),
                $audioTranscriptionBcp47LanguageCode
            );
        } catch (Throwable $throwable) {
            throw new Exception('', null, $throwable);
        }
    }

    /**
     * @throws Exception
     */
    public function updateTranslationTask(
        HappyScribeTranslationTask $happyScribeTranslationTask
    ): void
    {
        try {
            $response = $this->httpClient->request(
                Request::METHOD_GET,
                "$this->apiUrl/task/transcription_translation/{$happyScribeTranslationTask->getId()}",
                [
                    'headers' => [
                        'Content-Type'  => 'application/json',
                        'Accept'        => 'application/json',
                        'Authorization' => "Bearer $this->apiKey"
                    ]
                ]
            );

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                throw new Exception(
                    "Instead of status code '" . Response::HTTP_OK . "', HappyScribe returned status code '{$response->getStatusCode()}' with message '{$response->getContent()}'"
                );
            }

            $content = $response->getContent();

            $content = json_decode($content, true);

            $happyScribeTranslationTask->setState(
                HappyScribeTranslationTaskState::from($content['state'])
            );

            if (array_key_exists('translatedTranscriptionId', $content)) {
                $happyScribeTranslationTask->setTranslatedTranscriptionId($content['translatedTranscriptionId']);
            }

        } catch (Throwable $throwable) {
            throw new Exception('', null, $throwable);
        }
    }
}
