<?php

namespace App\Tests\ApplicationTests\Helper\BrowserExtension;


use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class RecordingSessionHelper
{
    public static function createRecordingSession(
        KernelBrowser $client,
    ): Crawler
    {
        $isFollowingRedirects = $client->isFollowingRedirects();

        $client->followRedirects();

        $client->request(
            'GET',
            '/api/extension/v1/account/session-info'
        );

        $crawler = $client->request(
            'POST',
            '/api/extension/v1/recordings/recording-sessions/'
        );

        $client->followRedirects($isFollowingRedirects);

        return $crawler;
    }

    public static function uploadChunks(
        KernelBrowser $client,
        string $postUrl
    ): void
    {
        foreach (['1', '2', '3'] as $videoChunkId) {
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
    }
}
