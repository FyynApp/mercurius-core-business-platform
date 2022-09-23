<?php

namespace App\DataFixtures\Feature\Account;

use App\Entity\Feature\Account\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class UserFixture extends Fixture
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
        $user->setPassword($this->userPasswordHasher->hashPassword($user, 'test123'));

        $manager->persist($user);
        $manager->flush();
    }
}
