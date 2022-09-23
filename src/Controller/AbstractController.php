<?php

namespace App\Controller;

use App\Entity\Feature\Account\User;
use LogicException;


abstract class AbstractController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
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
