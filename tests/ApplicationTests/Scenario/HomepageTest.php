<?php

namespace App\Tests\ApplicationTests\Scenario;

use App\VideoBasedMarketing\Account\Infrastructure\DataFixture\RegisteredUserFixture;
use App\VideoBasedMarketing\Account\Infrastructure\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class HomepageTest
    extends WebTestCase
{
    public function testVisitingWhileNotLoggedIn()
    {
        $client = static::createClient();
        $client->followRedirects();
        $client->request('GET', '/en/');
        $this->assertSelectorTextContains('h1', 'The Fyyn.io Browser Extension');
    }

    public function testThatRequestingTheHomepageWithGermanLanguageUrlWorks()
    {
        $client = static::createClient();
        $client->followRedirects();
        $client->request('GET', '/de/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Die Fyyn.io Browser Extension');
    }

    public function testVisitingWhileLoggedIn()
    {
        $client = static::createClient();
        $container = static::getContainer();

        $userRepository = $container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => RegisteredUserFixture::EMAIL]);

        $client->loginUser($user);
        $client->request('GET', '/en/');
        $this->assertResponseRedirects('/en/my/dashboard');
    }
}
