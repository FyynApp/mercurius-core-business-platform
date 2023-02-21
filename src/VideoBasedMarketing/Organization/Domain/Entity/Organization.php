<?php

namespace App\VideoBasedMarketing\Organization\Domain\Entity;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
#[ORM\Table(name: 'organizations')]
class Organization
{
    public function __construct(
        User $owningUser
    )
    {
        $this->owningUser = $owningUser;
    }

    #[ORM\OneToOne(
        inversedBy: 'ownedOrganization',
        targetEntity: User::class,
        cascade: ['persist']
    )]
    #[ORM\JoinColumn(
        name: 'owning_users_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private readonly User $owningUser;

    public function getOwningUser(): User
    {
        return $this->owningUser;
    }

    #[ORM\Column(
        type: 'string',
        length: 256,
        unique: false,
        nullable: true
    )]
    private ?string $name;

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
