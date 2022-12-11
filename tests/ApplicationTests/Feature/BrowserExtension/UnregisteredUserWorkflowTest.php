<?php

namespace App\Tests\ApplicationTests\Feature\BrowserExtension;

use App\Tests\ApplicationTests\Helper\BrowserExtension\RecordingSessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class UnregisteredUserWorkflowTest
    extends WebTestCase
{
    public function test(): void
    {
        $client = static::createClient();

        RecordingSessionHelper::createRecordingSession($client);

        $structuredResponse = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $recordingSessionId = $structuredResponse['settings']['recordingSessionId'];

        $postUrl = mb_ereg_replace(
            'http://localhost',
            '',
            $structuredResponse['settings']['postUrl']
        );

        RecordingSessionHelper::uploadChunks(
            $client,
            $postUrl,
            $recordingSessionId
        );

        $structuredResponse = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertSame(
            200,
            $structuredResponse['status']
        );

        $this->assertSame(
            "http://localhost/generated-content/recording-sessions/$recordingSessionId/recording-preview-video-poster.webp",
            $structuredResponse['preview']
        );

        $this->assertStringStartsWith(
            "http://localhost/recordings/recording-sessions/$recordingSessionId/recording-preview-asset-redirect?random=",
            $structuredResponse['previewVideo']
        );
    }
}
