<?php

namespace App\Tests\EndToEndTests\Scenario\Shared;

use App\Tests\EndToEndTests\Helper\AccountHelper;
use Symfony\Component\Panther\PantherTestCase;

class HomepageTest extends PantherTestCase
{
    public function testMyApp(): void
    {
        $client = static::createPantherClient();
        $client->request('GET', '/en/extension');

        $this->assertPageTitleSame('Fyyn — About');

        AccountHelper::cleanup($client);

        AccountHelper::signUp(
            $client,
            'end2endtest.user.1@example.com',
            'test123'
        );
    }
}
