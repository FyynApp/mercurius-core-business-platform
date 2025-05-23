<?php

namespace App\Tests\ApplicationTests\Helper;


use App\VideoBasedMarketing\Account\Infrastructure\DataFixture\RegisteredExtensionOnlyUserFixture;
use App\VideoBasedMarketing\Account\Infrastructure\Repository\UserRepository;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class RecordingSessionHelper extends Assert
{
    public static function uploadChunks(
        KernelBrowser $client,
        string        $postUrl,
        string        $recordingSessionId
    ): void
    {
        foreach (['1', '2', '3'] as $videoChunkId) {
            $fs = new Filesystem();
            $fs->copy(
                __DIR__ . "/../../Resources/fixtures/video-chunks/video-chunk-$videoChunkId",
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

        Assert::assertFileEquals(
            __DIR__
            . '/../../'
            . 'Resources/fixtures/correctly-encoded-recording-preview-video-poster.' . php_uname('m') . '.webp',

            __DIR__
            . '/../../../'
            . 'public/generated-content/recording-sessions/'
            . $recordingSessionId
            . '/recording-preview-video-poster.webp'
        );

        Assert::assertFileEquals(
            __DIR__
            . '/../../'
            . 'Resources/fixtures/correctly-encoded-recording-preview-video.webm',

            __DIR__
            . '/../../../'
            . 'public/generated-content/recording-sessions/'
            . $recordingSessionId
            . '/recording-preview-video.webm'
        );
    }

    public static function makeRecordingSession(
        KernelBrowser $client
    ): void
    {
        BrowserExtensionHelper::createRecordingSession($client);

        $structuredResponse = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $recordingSessionId = $structuredResponse['settings']['recordingSessionId'];
        $recordingSessionFinishedTargetUrl = $structuredResponse['settings']['recordingSessionFinishedTargetUrl'];
        $postUrl = $structuredResponse['settings']['postUrl'];


        RecordingSessionHelper::uploadChunks(
            $client,
            $postUrl,
            $recordingSessionId
        );

        $client->request(
            'GET',
            $recordingSessionFinishedTargetUrl
        );
    }
}
