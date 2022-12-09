<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\DataFixture;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Enum\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class RegisteredUserFixture
    extends Fixture
{
    const EMAIL = 'registered.user@example.com';

    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail(self::EMAIL);
        $user->setIsVerified(true);
        $user->addRole(Role::REGISTERED_USER);
        $user->setPassword(
            $this->userPasswordHasher->hashPassword(
                $user,
                'test123'
            )
        );

        $manager->persist($user);
        $manager->flush();
    }
}
