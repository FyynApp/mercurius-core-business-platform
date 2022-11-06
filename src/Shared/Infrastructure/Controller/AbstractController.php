<?php

namespace App\Shared\Infrastructure\Controller;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;


abstract class AbstractController
    extends SymfonyAbstractController
{
    public function getUser(): ?User
    {
        /** @var null|User $user */
        $user = parent::getUser();

        if (is_null($user)) {
            return null;
        }

        if (get_class($user) !== User::class) {
            throw new LogicException('Unexpectedly, $user has class ' . get_class($user));
        }

        return $user;
    }
}
