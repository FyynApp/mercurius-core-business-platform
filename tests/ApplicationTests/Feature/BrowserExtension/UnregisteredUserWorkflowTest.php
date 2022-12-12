<?php

namespace App\Tests\ApplicationTests\Feature\BrowserExtension;

use App\Tests\ApplicationTests\Helper\BrowserExtensionHelper;
use App\Tests\ApplicationTests\Helper\RecordingSessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class UnregisteredUserWorkflowTest
    extends WebTestCase
{
    public function test(): void
    {
        $client = static::createClient();

        BrowserExtensionHelper::createRecordingSession($client);

        $structuredResponse = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $recordingSessionId = $structuredResponse['settings']['recordingSessionId'];
        $recordingSessionFinishedTargetUrl = $structuredResponse['settings']['recordingSessionFinishedTargetUrl'];

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

        $client->followRedirects();
        $crawler = $client->request(
            'GET',
            $recordingSessionFinishedTargetUrl
        );

        $this->assertSame(
            'http://localhost/en/account/claim',
            $crawler->getUri()
        );

        $this->assertSame(
            'Hey there,',
            $crawler->filter('h1')->first()->text()
        );
    }
}
