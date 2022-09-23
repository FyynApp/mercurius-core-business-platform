<?php

namespace App\Tests\Application\Feature\Recordings;

use App\Entity\Feature\Recordings\RecordingSession;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class UnregisteredUsersTest extends WebTestCase
{
    public function testCreateRecordingSessionForBrowserExtension()
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
