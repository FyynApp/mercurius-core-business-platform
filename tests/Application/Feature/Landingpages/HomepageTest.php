<?php

namespace App\Tests\Application\Feature\Landingpages;

use App\BoundedContext\Account\Application\DataFixture\UserFixture;
use App\BoundedContext\Account\Domain\Repository\UserRepository;
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
