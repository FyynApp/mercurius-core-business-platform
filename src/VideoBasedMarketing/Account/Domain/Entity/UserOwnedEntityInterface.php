<?php

namespace App\VideoBasedMarketing\Account\Domain\Entity;


interface UserOwnedEntityInterface
{
    public function getId(): ?string;

    public function getUser(): User;
}
