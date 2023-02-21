<?php

namespace App\VideoBasedMarketing\Organization\Domain\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;


#[ORM\Entity]
#[ORM\Table(name: 'organization_invitations')]
class Invitation
{
    /**
     * @throws Exception
     */
    public function __construct(
        Organization $organization,
        string       $email
    )
    {
        $this->organization = $organization;
        $this->email = $email;
        $this->createdAt = DateAndTimeService::getDateTime();
    }

    #[ORM\ManyToOne(
        targetEntity: Organization::class,
        cascade: ['persist'],
        inversedBy: 'invitations'
    )]
    #[ORM\JoinColumn(
        name: 'owner_users_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private readonly Organization $organization;

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    #[ORM\Column(
        type: 'string',
        length: 256,
        unique: false,
        nullable: false
    )]
    private readonly string $email;

    public function getEmail(): string
    {
        return $this->email;
    }

    #[ORM\Column(
        type: 'datetime',
        nullable: false
    )]
    private DateTime $createdAt;

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }
}
