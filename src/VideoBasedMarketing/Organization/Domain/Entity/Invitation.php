<?php

namespace App\VideoBasedMarketing\Organization\Domain\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;


#[ORM\Entity]
#[ORM\Table(name: 'organization_invitations')]
class Invitation
    implements OrganizationOwnedEntityInterface
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


    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(
        type: Types::GUID,
        unique: true
    )]
    private ?string $id = null;

    /**
     * @throws Exception
     */
    public function getId(): string
    {
        if (is_null($this->id)) {
            throw new Exception('Entity of class ' . self::class . ' does not yet have an id.');
        }
        return $this->id;
    }


    #[ORM\ManyToOne(
        targetEntity: Organization::class,
        cascade: ['persist']
    )]
    #[ORM\JoinColumn(
        name: 'organizations_id',
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
        type: Types::STRING,
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
        type: Types::DATETIME_MUTABLE,
        nullable: false
    )]
    private DateTime $createdAt;

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }
}
