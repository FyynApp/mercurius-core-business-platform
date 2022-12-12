<?php

namespace App\Tests\ApplicationTests\Feature;

use App\VideoBasedMarketing\Account\Infrastructure\DataFixture\RegisteredUserFixture;
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
        $this->assertSelectorTextContains('h1', 'Increase sales using Video.');
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

    public function testThatRequestingTheHomepageWithGermanLanguageUrlWorks()
    {
        $client = static::createClient();
        $client->request('GET', '/de/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Verkaufsergebnisse mit Videos steigern.');
    }
}
