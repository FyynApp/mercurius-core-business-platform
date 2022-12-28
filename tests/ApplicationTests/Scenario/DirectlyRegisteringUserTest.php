<?php

namespace App\Tests\ApplicationTests\Scenario;


use App\Tests\ApplicationTests\Helper\UnregisteredUserHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mime\Email;

// This test verifies how the site behaves if users register themselves without
// going through the browser extension / claim process.
class DirectlyRegisteringUserTest
    extends WebTestCase
{
    public function test(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        $crawler = $client->request('GET', '/');
        $crawler = $client->click(
            $crawler
                ->filter('[data-test-id="extensionHomepageSignUpCta"]')
                ->link()
        );

        $form = $crawler->selectButton('Sign up')->form();
        $form['sign_up[email]'] = 'j.doe@example.com';
        $form['sign_up[plainPassword]'] = 'j.doe@example.com';
        $form['sign_up[agreeTerms]'] = 'on';

        $client->followRedirects(false);
        $crawler = $client->submit($form);


        $this->assertEmailCount(1);

        /** @var Email $email */
        $email = $this->getMailerMessage();

        $crawler->clear();
        $crawler->addHtmlContent($email->getHtmlBody());

        $this->assertSame(
            "It's nearly done!",
            $crawler->filter('h2')->first()->text()
        );


        $client->followRedirects(true);
        $crawler = $client->request(
            'GET',
            $crawler->filter('a')->first()->attr('href')
        );

        $this->assertSelectorTextContains(
            'body',
            'Your email address has been verified.'
        );

        // verifies that we get the site with the minimalistic extension-only navigation
        $this->assertEmpty(
            $crawler->filter(
                '[data-test-id="main-navigation-entry-videobasedmarketing.dashboard.presentation.show_registered"]'
            )
        );

        $this->assertSame(
            'http://localhost/en/my/recordings/videos/',
            $client->getRequest()->getUri()
        );
    }
}
