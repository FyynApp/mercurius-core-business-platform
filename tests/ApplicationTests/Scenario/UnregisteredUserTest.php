<?php

namespace App\Tests\ApplicationTests\Scenario;


use App\Tests\ApplicationTests\Helper\UnregisteredUserHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UnregisteredUserTest
    extends WebTestCase
{
    public function testSiteMostlyLimitsUnregisteredUsersToClaimPage(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        UnregisteredUserHelper::createUnregisteredUser($client);

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

    public function testClaimPageRecognizesIfUserDoesNotYetHaveAnyVideos(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        UnregisteredUserHelper::createUnregisteredUser($client);

        $crawler = $client->request('GET', '/en/account/claim');

        $this->assertSelectorTextSame(
            '[data-test-id=note2]',
            'Once you use it to create recordings, they are stored securely and reliably, making them accessible for you easily at any time.'
        );
    }
}
