<?php

namespace App\Tests\ApplicationTests\Scenario;


use App\Tests\ApplicationTests\Helper\UnregisteredUserHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UnregisteredUserCreatedViaExtensionTest
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
                '/en/account/sign-in',
                '/api/extension/v1/account/session-info',
                '/en/account/sign-up',
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

        $client->request('GET', '/en/account/claim');

        $this->assertSelectorTextSame(
            '[data-test-id=note2]',
            'Once you use it to create videos, they are stored securely and reliably, making them accessible for you easily at any time.'
        );
    }

    public function testReclaimingAlreadyClaimedVerifiedEmail(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        UnregisteredUserHelper::createUnregisteredUser($client);

        $crawler = $client->request('GET', '/en/account/claim');

        $createAccountButton = $crawler->selectButton('Create account');
        $form = $createAccountButton->form();

        $form['claim_unregistered_user[email]'] = 'j.doe@example.com';

        $client->submit($form);
        $this->assertSelectorTextSame('h1', 'A final step...');


        UnregisteredUserHelper::createUnregisteredUser($client);
        $crawler = $client->request('GET', '/en/account/claim');

        $createAccountButton = $crawler->selectButton('Create account');
        $form = $createAccountButton->form();

        $form['claim_unregistered_user[email]'] = 'j.doe@example.com';
        $client->submit($form);

        $this->assertSelectorTextSame('h1', 'A final step...');
    }
}
