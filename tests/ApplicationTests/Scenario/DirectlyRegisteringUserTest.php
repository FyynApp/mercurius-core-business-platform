<?php

namespace App\Tests\ApplicationTests\Scenario;


use App\Tests\ApplicationTests\Helper\UnregisteredUserHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

// This test verifies how the site behaves if users register themselves without
// going through the browser extension / claim process.
class DirectlyRegisteringUserTest
    extends WebTestCase
{
    public function test(): void
    {
        $client = static::createClient();
        $client->followRedirects();
    }
}
