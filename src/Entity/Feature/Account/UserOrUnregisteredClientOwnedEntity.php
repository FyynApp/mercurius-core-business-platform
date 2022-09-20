<?php

namespace App\Entity\Feature\Account;

use InvalidArgumentException;

abstract class UserOrUnregisteredClientOwnedEntity
{
    protected ?User $user = null;
    protected ?UnregisteredClient $unregisteredClient = null;

    public function __construct(
        ?User $user,
        ?UnregisteredClient $unregisteredClient
    )
    {
        if (is_null($user) && is_null($unregisteredClient)) {
            throw new InvalidArgumentException('$user and $unregisteredClient cannot both be null.');
        }

        if (!is_null($user) && !is_null($unregisteredClient)) {
            throw new InvalidArgumentException('$user and $unregisteredClient cannot both be set.');
        }

        $this->user = $user;
        $this->unregisteredClient = $unregisteredClient;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getUnregisteredClient(): ?UnregisteredClient
    {
        return $this->unregisteredClient;
    }

    public function isUserOwned(): bool
    {
        return !is_null($this->user);
    }

    public function isUnregisteredClientOwned(): bool
    {
        return !is_null($this->unregisteredClient);
    }
}
