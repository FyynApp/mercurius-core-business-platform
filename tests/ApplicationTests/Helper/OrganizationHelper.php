<?php

namespace App\Tests\ApplicationTests\Helper;


use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Mime\Email;

class OrganizationHelper
{
    public static function inviteEmailToOrganization(
        KernelBrowser $client,
        WebTestCase   $webTestCase,
        string        $email
    ): Crawler
    {
        $crawler = $client->request(
            'GET',
            '/en/my/current-organization/overview'
        );

        $form = $crawler->selectButton('Send invitation')->form();
        $form['email'] = $email;

        $client->followRedirects(false);
        $crawler = $client->submit($form);

        /** @var Email $email */
        $email = $webTestCase->getMailerMessage();

        $crawler->clear();
        $crawler->addHtmlContent($email->getHtmlBody());

        $client->followRedirects();

        $client->request(
            'POST',
            '/account/sign-out'
        );

        $crawler = $client->request(
            'GET',
            $crawler->filter('[data-test-id="acceptCta"]')->first()->attr('href')
        );

        $form = $crawler->selectButton('Join organization')->form();
        $client->followRedirects(true);
        $crawler = $client->submit($form);

        return $crawler;
    }
}
