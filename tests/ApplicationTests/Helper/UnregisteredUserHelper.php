<?php

namespace App\Tests\ApplicationTests\Helper;


use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

class UnregisteredUserHelper extends Assert
{
    public static function createUnregisteredUser(
        KernelBrowser $client,
    ): Crawler
    {
        return BrowserExtensionHelper::getSessionInfo($client);
    }
}
