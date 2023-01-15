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
            '/account/thirdpartyauth/linkedin/return?code=AQQ4F6tzWOI0f9uPJpI9hunOi3zIiX0A-SyYw5ZcLJvGe21uhkYQZGoQavQjbtGNr7DM7lr7HbzydqOwd2fI-OJ37un8apx8QwQvcwbDQbsokz2VSNciESGH2twb4kwd-SxFpk5VleDWGqnrLlrGNGrZGUU8dtSYLqVw4CFrn3Lg_GxC__eRlQWihA1B8l6qo77n5wIPjFZtrpfEM9o&state=4d4a73452f61cf1ce1879ff95a633436'
        );

        $this->assertResponseStatusCodeSame(200);

        $em = $container->get(EntityManagerInterface::class);
        /** @var User[] $users */
        $users = $em->getRepository(User::class)->findAll();

        $this->assertCount(
            3,
            $users
        );

        $this->assertSame(
            'thirdparty.linkedin.user@example.com',
            $users[2]->getEmail()
        );
    }
}
