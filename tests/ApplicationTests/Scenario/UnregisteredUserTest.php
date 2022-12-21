<?php

namespace App\Tests\ApplicationTests\Scenario;


use App\Tests\ApplicationTests\Helper\BrowserExtensionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UnregisteredUserTest
    extends WebTestCase
{
    public function testSiteMostlyLimitsUnregisteredUsersToClaimPage(): void
    {
        $client = static::createClient();

        $client->followRedirects();

        // This creates an unregistered user
        BrowserExtensionHelper::getSessionInfo($client);

        foreach (
            [
                '/',
                '/en/',
                '/en/features',
                '/en/pricing',
                '/en/account/sign-in',
                '/en/account/sign-up',
            ]
            as $path
        ) {
            $client->request('GET', $path);

            $this->assertSame(
                'http://localhost/en/account/claim',
                $client->getRequest()->getUri()
            );
        }

        foreach (
            [
                '/en/account/claim',
                '/api/extension/v1/account/session-info'
            ]
            as $path
        ) {
            $client->request('GET', $path);

            $this->assertSame(
                "http://localhost$path",
                $client->getRequest()->getUri()
            );
        }
    }
}
