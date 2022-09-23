<?php

namespace App\Service\Feature\Account;

use App\Entity\Feature\Account\User;
use Doctrine\ORM\EntityManagerInterface;


class AccountService
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    )
    {
        $this->entityManager = $entityManager;
    }

    public function userMustBeRedirectedToThirdPartyAuthLinkedinEndpoint(string $email): bool
    {
        /** @var ?User $user */
        $user = $this->entityManager->getRepository(User::class)
                                    ->findOneBy(
                                        ['email' => $email]
                                    );

        if (is_null($user)) {
            return false;
        }

        if (is_null($user->getThirdPartyAuthLinkedinResourceOwner())) {
            return false;
        }

        return true;
    }
}
