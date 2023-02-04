<?php

namespace App\Tests\EndToEndTests\Helper;


use App\VideoBasedMarketing\Account\Domain\Entity\User;
use Facebook\WebDriver\WebDriverBy;
use Symfony\Component\Panther\Client as PantherClient;

class AccountHelper
{
    public static function cleanup(
        PantherClient $client
    ): void
    {
        $client->get('/en/account/cleanup');
        $client->takeScreenshot('/var/tmp/cleanup.png');
    }


    public static function signUp(
        PantherClient $client,
        string        $email,
        string        $password
    ): void
    {
        $client->get('/en/account/sign-up');
        $crawler = $client->refreshCrawler();

        $form = $crawler->selectButton('Sign up')->form();
        $form['sign_up[email]'] = $email;
        $form['sign_up[plainPassword]'] = $password;

        $client->findElement(WebDriverBy::id('sign_up_agreeTerms'))->click();

        $client->submit($form);

        $client->takeScreenshot('/var/tmp/foo1.png');

        self::setUserVerified($client, $email);

        $client->takeScreenshot('/var/tmp/foo2.png');
    }

    private static function setUserVerified(
        PantherClient $client,
        string        $email
    ): void
    {
        $client->get('/en/account/sign-up/verify-email-directly?email=' . urlencode($email));
    }

    public static function signIn(
        PantherClient $client,
        User $user
    ): void
    {
        $client->get('/en/account/sign-in');
        $crawler = $client->refreshCrawler();

        $form = $crawler->selectButton('Sign in')->form();
        $form['_username'] = $user->getEmail();
        $form['_password'] = 'test123';

        echo "\nloginUser: {$user->getPassword()}\n";

        $client->submit($form);
    }
}
