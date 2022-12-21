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
        self::getSessionInfo($client);

        $isFollowingRedirects = $client->isFollowingRedirects();

        $crawler = $client->request(
            'POST',
            '/api/extension/v1/recordings/recording-sessions/'
        );

        $client->followRedirects($isFollowingRedirects);

        return $crawler;
    }

    public static function getSessionInfo(
        KernelBrowser $client,
    ): Crawler
    {
        $isFollowingRedirects = $client->isFollowingRedirects();

        $client->followRedirects();

        $crawler = $client->request(
            'GET',
            '/api/extension/v1/account/session-info'
        );

        $client->followRedirects($isFollowingRedirects);

        return $crawler;
    }
}
