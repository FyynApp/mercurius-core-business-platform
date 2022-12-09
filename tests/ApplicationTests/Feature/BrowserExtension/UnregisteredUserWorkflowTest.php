<?php

namespace App\Tests\ApplicationTests\Feature\BrowserExtension;

use App\Tests\ApplicationTests\Helper\BrowserExtension\RecordingSessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;


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

        foreach (['1', '2'] as $videoChunkId) {
            $fs = new Filesystem();
            $fs->copy(
                __DIR__ . "/../../../Resources/fixtures/video-chunks/video-chunk-$videoChunkId",
                "/var/tmp/video-chunk-$videoChunkId",
                true
            );
            $videoChunkFile = new UploadedFile(
                "/var/tmp/video-chunk-$videoChunkId",
                "$videoChunkId.webm",
                'video/webm;codecs=vp9,opus',
                null,
                true
            );

            $client->request(
                'POST',
                $postUrl,
                ['video' => "$videoChunkId.webm"],
                ['video-blob' => $videoChunkFile],
                [
                    'CONTENT_TYPE' => 'multipart/form-data',
                ],
            );
        }

        $client->request(
            'POST',
            $postUrl,
            ['recordingDone' => 'true'],
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
            ],
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
