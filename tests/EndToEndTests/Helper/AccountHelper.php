<?php

namespace App\Tests\EndToEndTests\Helper;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use Symfony\Component\Panther\Client as PantherClient;


class AccountHelper
{
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

        $client->submit($form);
    }
}
