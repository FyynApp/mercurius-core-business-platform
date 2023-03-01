<?php

namespace App\Tests\ApplicationTests\Scenario\Organization;


use App\Tests\ApplicationTests\Helper\AccountHelper;
use App\VideoBasedMarketing\Account\Infrastructure\DataFixture\RegisteredExtensionOnlyUserFixture;
use App\VideoBasedMarketing\Account\Infrastructure\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mime\Email;

class InvitationTest
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
            '/en/my/organization/overview'
        );

        $form = $crawler->selectButton('Send invitation')->form();
        $form['email'] = 'foo.bar@example.com';

        $client->followRedirects(false);
        $crawler = $client->submit($form);

        $this->assertEmailCount(1);

        /** @var Email $email */
        $email = $this->getMailerMessage();

        $crawler->clear();
        $crawler->addHtmlContent($email->getHtmlBody());

        $this->assertSame(
            'Join organization',
            $crawler->filter('[data-test-id="acceptCta"]')->first()->text()
        );


        $client->followRedirects();

        $client->request(
            'POST',
            '/account/sign-out'
        );

        $crawler = $client->request(
            'GET',
            $crawler->filter('[data-test-id="acceptCta"]')->first()->attr('href')
        );

        $this->assertSelectorTextContains(
            'body',
            'Do you want to join the organization owned by registered.extensiononly.user@example.com?'
        );

        $this->assertSame(
            'Join organization',
            $crawler->filter('[data-test-id="acceptCta"]')->first()->text()
        );

        $form = $crawler->selectButton('Join organization')->form();
        $client->followRedirects(false);
        $crawler = $client->submit($form);

        /** @var Email $email */
        $email = $this->getMailerMessage();

        $client->followRedirect();
        $client->followRedirect();

        $this->assertSelectorTextContains(
            'body',
            'Fyyn.io You have successfully joined the organization. A final step...'
        );

        $crawler = AccountHelper::verifyUserByEmail($client, $email);

        $crawler = $client->request(
            'GET',
            '/en/my/organization/switch'
        );

        $this->assertSame(
            'registered.extensiononly.user@example.com',
            $crawler->filter('[data-test-class="ownedByNote"]')->first()->text()
        );

        $this->assertSame(
            'Owned by you',
            $crawler->filter('[data-test-class="ownedByNote"]')->eq(1)->text()
        );
    }
}
