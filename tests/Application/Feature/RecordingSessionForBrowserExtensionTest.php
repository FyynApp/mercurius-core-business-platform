<?php

namespace App\Tests\Application\Feature;

use App\VideoBasedMarketing\Account\Infrastructure\DataFixture\RegisteredUserFixture;
use App\VideoBasedMarketing\Account\Infrastructure\Repository\UserRepository;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class RecordingSessionForBrowserExtensionTest
    extends WebTestCase
{
    public function testRegisteredUser(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $userRepository = $container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => RegisteredUserFixture::EMAIL]);

        $client->loginUser($user);

        $em = $container->get(EntityManagerInterface::class);

        $this->assertCount(0, $em->getRepository(RecordingSession::class)->findAll());

        $client->request(
            'POST',
            '/api/extension/v1/recordings/recording-sessions/'
        );
        $this->assertResponseRedirects();

        $sessions = $em->getRepository(RecordingSession::class)->findAll();

        $this->assertCount(1, $sessions);

        /** @var RecordingSession $session */
        $session = $sessions[0];

        $this->assertTrue($session->getUser()->isRegistered());

        $structuredResponse = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(
            300,
            $structuredResponse['settings']['maxRecordingTime']
        );
    }

    public function testUnregisteredUser(): void
    {
        $client = static::createClient();

        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $this->assertCount(0, $em->getRepository(RecordingSession::class)->findAll());

        $client->request(
            'GET',
            '/api/extension/v1/account/session-info'
        );
        $client->followRedirect();

        $client->request(
            'POST',
            '/api/extension/v1/recordings/recording-sessions/'
        );
        $this->assertResponseRedirects();

        $sessions = $em->getRepository(RecordingSession::class)->findAll();

        $this->assertCount(1, $sessions);

        /** @var RecordingSession $session */
        $session = $sessions[0];

        $this->assertFalse($session->getUser()->isRegistered());

        $structuredResponse = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(
            60,
            $structuredResponse['settings']['maxRecordingTime']
        );
    }
}
