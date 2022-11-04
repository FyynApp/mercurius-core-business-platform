<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\DataFixture;

use App\VideoBasedMarketing\Account\Domain\Entity\Role;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class UserFixture
    extends Fixture
{
    const TEST_USER_EMAIL = 'j.doe@example.com';

    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail(self::TEST_USER_EMAIL);
        $user->setIsVerified(true);
        $user->addRole(Role::REGISTERED_USER);
        $user->setPassword($this->userPasswordHasher->hashPassword($user, 'test123'));

        $manager->persist($user);
        $manager->flush();
    }
}
