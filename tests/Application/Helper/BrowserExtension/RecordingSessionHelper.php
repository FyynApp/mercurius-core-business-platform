<?php

namespace App\Tests\Application\Helper\BrowserExtension;


use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

class RecordingSessionHelper
{
    public static function createRecordingSession(
        KernelBrowser $client,
    ): Crawler
    {
        $client->request(
            'GET',
            '/api/extension/v1/account/session-info'
        );
        $client->followRedirect();

        return $client->request(
            'POST',
            '/api/extension/v1/recordings/recording-sessions/'
        );
    }
}
