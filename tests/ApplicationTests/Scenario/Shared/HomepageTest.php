<?php

namespace App\Tests\ApplicationTests\Scenario\Shared;

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
        $this->assertSelectorTextContains(
            'h1',
            'Fyyn.io allows you to create screen and webcam recordings right from your browser, and gives you the tools you need to make them outperform. 🚀'
        );
    }

    public function testThatRequestingTheHomepageWithGermanLanguageUrlWorks()
    {
        $client = static::createClient();
        $client->followRedirects();
        $client->request('GET', '/de/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains(
            'h1',
            'Fyyn.io allows you to create screen and webcam recordings right from your browser, and gives you the tools you need to make them outperform. 🚀'
        );
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
