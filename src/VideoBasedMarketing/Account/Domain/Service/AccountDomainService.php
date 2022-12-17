<?php

namespace App\VideoBasedMarketing\Account\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Enum\Role;
use App\VideoBasedMarketing\Account\Presentation\Service\AccountPresentationService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use LogicException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;


class AccountDomainService
{
    private EntityManagerInterface $entityManager;

    private AccountPresentationService $presentationService;

    public function __construct(
        EntityManagerInterface  $entityManager,
        AccountPresentationService $presentationService
    )
    {
        $this->entityManager = $entityManager;
        $this->presentationService = $presentationService;
    }


    /**
     * @throws Exception
     */
    public function createUnregisteredUser(
        bool $asExtensionOnlyUser = false
    ): User
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

        if ($asExtensionOnlyUser) {
            $user->addRole(Role::EXTENSION_ONLY_USER);
        }

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
     * @throws TransportExceptionInterface
     */
    public function handleUnregisteredUserClaim(
        User   $unregisteredUser,
        string $claimEmail
    ): bool
    {
        if ($unregisteredUser->isRegistered()) {
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

        $unregisteredUser->setEmail($claimEmail);
        $unregisteredUser->makeRegistered();

        $this->entityManager->persist($unregisteredUser);
        $this->entityManager->flush();

        $this
            ->presentationService
            ->sendVerificationEmailForClaimedUser($unregisteredUser);

        return true;
    }
}
