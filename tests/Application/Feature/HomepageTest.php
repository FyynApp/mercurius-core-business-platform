<?php

namespace App\Tests\Application\Feature;

use App\VideoBasedMarketing\Account\Infrastructure\DataFixture\UserFixture;
use App\VideoBasedMarketing\Account\Infrastructure\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class HomepageTest
    extends WebTestCase
{
    public function testVisitingWhileNotLoggedIn()
    {
        $client = static::createClient();
        $client->request('GET', '/en/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'About');
    }

    public function testVisitingWhileLoggedIn()
    {
        $client = static::createClient();
        $container = static::getContainer();

        $userRepository = $container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => UserFixture::TEST_USER_EMAIL]);

        $client->loginUser($user);
        $client->request('GET', '/en/');
        $this->assertResponseRedirects('/en/my/dashboard');
    }
}
