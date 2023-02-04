<?php

namespace App\Tests\EndToEndTests\Scenario\Shared;

use Symfony\Component\Panther\PantherTestCase;

class HomepageTest extends PantherTestCase
{
    public function testMyApp(): void
    {
        $client = static::createPantherClient();
        $client->request('GET', '/en/extension');

        $this->assertPageTitleSame('Fyyn — About');
    }
}
