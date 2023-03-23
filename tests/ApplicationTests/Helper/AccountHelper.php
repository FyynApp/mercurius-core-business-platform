<?php

namespace App\Tests\ApplicationTests\Helper;


use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Mime\Email;

class AccountHelper
{
    public static function verifyUserByEmail(
        KernelBrowser $client,
        Email         $email
    ): Crawler
    {
        $crawler = $client->getCrawler();

        $crawler->clear();
        $crawler->addHtmlContent($email->getHtmlBody());

        Assert::assertSame(
            "It's nearly done!",
            $crawler->filter('h2')->first()->text()
        );


        $client->followRedirects(true);
        $crawler = $client->request(
            'GET',
            $crawler->filter('a')->first()->attr('href')
        );

        Assert::assertStringContainsString(
            'Your email address has been verified.',
            $crawler->filter('body')->first()->text()
        );

        return $crawler;
    }

    public static function signOut(
        KernelBrowser $client
    ): Crawler
    {
        $isFollowingRedirects = $client->isFollowingRedirects();

        $client->followRedirects();
        $crawler = $client->request(
            'GET',
            '/'
        );
        $form = $crawler->filter('[data-test-id="signOutForm"]')->form();
        $crawler = $client->submit($form);

        $client->followRedirects($isFollowingRedirects);

        return $crawler;
    }
}
