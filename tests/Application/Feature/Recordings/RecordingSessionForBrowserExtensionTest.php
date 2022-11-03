<?php

namespace App\Tests\Application\Feature\Recordings;

use App\BoundedContext\Account\Application\DataFixture\UserFixture;
use App\BoundedContext\Account\Domain\Repository\UserRepository;
use App\Entity\Feature\Recordings\RecordingSession;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class RecordingSessionForBrowserExtensionTest
    extends WebTestCase
{
    public function testRegisteredUser()
    {
        $client = static::createClient();
        $container = static::getContainer();

        $userRepository = $container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => UserFixture::TEST_USER_EMAIL]);

        $client->loginUser($user);

        $em = $container->get(EntityManagerInterface::class);

        $this->assertCount(0, $em->getRepository(RecordingSession::class)->findAll());

        $client->request(
            'POST',
            '/api/feature/recordings/browser-extension-recording-sessions/'
        );
        $this->assertResponseRedirects();

        $sessions = $em->getRepository(RecordingSession::class)->findAll();

        $this->assertCount(1, $sessions);

        /** @var RecordingSession $session */
        $session = $sessions[0];

        $this->assertTrue($session->getUser()->isRegistered());

        $client->followRedirect();

        $structuredResponse = json_decode($client->getResponse()->getContent(), true);

        $this->assertTrue($structuredResponse['settings']['userIsRegistered']);
        $this->assertSame(
            UserFixture::TEST_USER_EMAIL,
            $structuredResponse['settings']['userName']
        );
    }

    public function testUnregisteredUser()
    {
        $client = static::createClient();

        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $this->assertCount(0, $em->getRepository(RecordingSession::class)->findAll());

        $client->request(
            'POST',
            '/api/feature/recordings/browser-extension-recording-sessions/'
        );
        $this->assertResponseRedirects();

        $sessions = $em->getRepository(RecordingSession::class)->findAll();

        $this->assertCount(1, $sessions);

        /** @var RecordingSession $session */
        $session = $sessions[0];

        $this->assertFalse($session->getUser()->isRegistered());

        $client->followRedirect();

        $structuredResponse = json_decode($client->getResponse()->getContent(), true);

        $this->assertFalse($structuredResponse['settings']['userIsRegistered']);
    }
}
