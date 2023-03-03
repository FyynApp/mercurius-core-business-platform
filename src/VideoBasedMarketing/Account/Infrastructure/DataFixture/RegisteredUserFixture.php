<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\DataFixture;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Enum\Role;
use App\VideoBasedMarketing\Account\Domain\Service\AccountDomainService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class RegisteredUserFixture
    extends Fixture
{
    const EMAIL = 'registered.user@example.com';

    private AccountDomainService $accountDomainService;

    public function __construct(
        AccountDomainService $accountDomainService
    )
    {
        $this->accountDomainService = $accountDomainService;
    }

    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        $user = $this->accountDomainService->createRegisteredUser(
            self::EMAIL,
            'test123',
            true
        );

        $manager->persist($user);
        $manager->flush();
    }
}
