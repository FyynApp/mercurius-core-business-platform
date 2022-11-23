<?php

namespace App\Shared\Infrastructure\Entity;


interface SupportsShortIdInterface
{
    public function getShortId(): ?string;

    public function setShortId(string $shortId): void;
}
