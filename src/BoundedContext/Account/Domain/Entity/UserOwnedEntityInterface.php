<?php

namespace App\BoundedContext\Account\Domain\Entity;


interface UserOwnedEntityInterface
{
    public function getId(): ?string;

    public function getUser(): User;
}
