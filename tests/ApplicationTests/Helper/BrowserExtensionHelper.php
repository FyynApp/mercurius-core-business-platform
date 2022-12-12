<?php

namespace App\Tests\ApplicationTests\Helper;


use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

class BrowserExtensionHelper extends Assert
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
}
