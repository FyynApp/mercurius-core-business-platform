<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\Service;


use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscription;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Entity\HappyScribeExport;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Entity\HappyScribeTranscription;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Enum\HappyScribeTranscriptionState;
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
                HappyScribeTranscriptionState::from($content['state'])
            );
        } catch (Throwable $throwable) {
            throw new Exception('', null, $throwable);
        }
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

    public function updateExport(
        HappyScribeExport $happyScribeExport
    ): void
    {

    }
}
