<?php

namespace App\Tests\ApplicationTests\Scenario;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\Container;


class RegistrationViaLinkedInTest
    extends WebTestCase
{
    public function test(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        /** @var Container $container */
        $container = $client->getContainer();

        $client->request(
            'GET',
            '/account/thirdpartyauth/linkedin/return'
        );

        $this->assertResponseStatusCodeSame(200);

        $em = $container->get(EntityManagerInterface::class);
        /** @var User[] $users */
        $users = $em->getRepository(User::class)->findAll();

        $this->assertSame(
            'thirdparty.linkedin.user@example.com',
            $users[sizeof($users) - 1]->getEmail()
        );
    }
}
