<?php

namespace App\VideoBasedMarketing\Account\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Enum\Role;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use LogicException;


class UserDomainService
{
    private EntityManagerInterface $entityManager;


    public function __construct(
        EntityManagerInterface $entityManager
    )
    {
        $this->entityManager = $entityManager;
    }


    /**
     * @throws Exception
     */
    public function createUnregisteredUser(): User
    {
        $user = new User();
        $user->setEmail(
            sha1(
                'fh45897z784787h!8997/%drh==iuh'
                . random_int(PHP_INT_MIN,  PHP_INT_MAX)
                . random_int(PHP_INT_MIN,  PHP_INT_MAX)
            )
            . '@unregistered.fyyn.io'
        );

        $user->addRole(Role::UNREGISTERED_USER);

        $user->setPassword(
            password_hash(
                random_int(PHP_INT_MIN, PHP_INT_MAX),
                PASSWORD_DEFAULT
            )
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * @throws Exception
     */
    public function handleUnregisteredUserClaim(
        User   $currentUser,
        string $claimEmail
    ): bool
    {
        if ($currentUser->isRegistered()) {
            throw new LogicException('Only unregistered user sessions can claim.');
        }

        /** @var User|null $existingUser */
        $existingUser = $this
            ->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $claimEmail]);

        if (!is_null($existingUser)) {
            throw new Exception("A user with email '$claimEmail' already exists.");
        }

        $currentUser->setEmail($claimEmail);
        $currentUser->makeRegistered();

        $this->entityManager->persist($currentUser);
        $this->entityManager->flush();

        return true;
    }
}
