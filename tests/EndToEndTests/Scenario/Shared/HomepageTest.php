<?php

namespace App\Tests\EndToEndTests\Scenario\Shared;

use App\Tests\EndToEndTests\Helper\AccountHelper;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Infrastructure\DataFixture\RegisteredExtensionOnlyUserFixture;
use App\VideoBasedMarketing\Account\Infrastructure\Repository\UserRepository;
use Symfony\Component\Panther\PantherTestCase;

class HomepageTest extends PantherTestCase
{
    public function testMyApp(): void
    {
        $client = static::createPantherClient();
        $client->request('GET', '/en/extension');

        $this->assertPageTitleSame('Fyyn — About');

        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);

        /** @var User $user */
        $user = $userRepository->findOneBy(['email' => RegisteredExtensionOnlyUserFixture::EMAIL]);

        echo $user->getUserIdentifier();

        AccountHelper::signIn($client, $user);

        $this->assertSelectorTextContains('foo', 'bar');
    }
}
