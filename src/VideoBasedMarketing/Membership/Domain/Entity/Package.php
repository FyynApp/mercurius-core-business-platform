<?php

namespace App\VideoBasedMarketing\Membership\Domain\Entity;

use App\VideoBasedMarketing\Membership\Domain\Enum\PackageName;


readonly class Package
{
    private PackageName $name;

    private float $price;


    public function __construct(
        PackageName $name,
        float       $price,
    )
    {
        $this->name = $name;
        $this->price = $price;
    }

    public function getName(): PackageName
    {
        return $this->name;
    }

    public function getNiceName(): string
    {
        return ucfirst($this->name->value);
    }

    public function getPrice(): float
    {
        return $this->price;
    }
}
