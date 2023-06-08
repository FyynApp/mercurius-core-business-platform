<?php

namespace App\Tests\ApplicationTests\Scenario\Account;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\NotSupported;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\TestContainer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class RegistrationViaLinkedInTest
    extends WebTestCase
{
    /**
     * @throws NotSupported
     * @throws Exception
     */
    public function test(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        /** @var TestContainer $container */
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
