<?php

namespace App\Tests\ApplicationTests\Scenario\RecordingRequests;

use App\Tests\ApplicationTests\Helper\AccountHelper;
use App\VideoBasedMarketing\Account\Infrastructure\DataFixture\RegisteredExtensionOnlyUserFixture;
use App\VideoBasedMarketing\Account\Infrastructure\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RecordingRequestsTest
    extends WebTestCase
{
    public function test(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $userRepository = $container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => RegisteredExtensionOnlyUserFixture::EMAIL]);

        $client->loginUser($user);

        $crawler = $client->request(
            'GET',
            '/en/my/recording-requests/'
        );

        $form = $crawler->selectButton('Create new request')->form();

        $client->submit($form);
        $crawler = $client->followRedirect();

        $form = $crawler->selectButton('Save changes')->form();
        $form['title'] = 'The request title';
        $form['requestText'] = 'The request text';

        $client->submit($form);
        $crawler = $client->followRedirect();

        $this->assertSame(
            'The request title',
            $crawler->filter('[data-test-id="recordingRequestTitle"]')->first()->attr('value')
        );

        $shareUrl = $crawler->filter('[data-test-id="recordingRequestShareUrl"]')->first()->attr('value');

        AccountHelper::signOut($client);

        $client->request(
            'GET',
            $shareUrl
        );
        $crawler = $client->followRedirect();

        $this->assertSelectorTextContains(
            'body',
            'User registered.extensiononly.user@example.com requested a video recording.'
        );

        $form = $crawler->selectButton('Start process')->form();

        $client->submit($form);
        $crawler = $client->followRedirect();

        $this->assertSelectorTextContains(
            'body',
            'The request text'
        );
    }
}
