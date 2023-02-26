<?php

namespace App\VideoBasedMarketing\Organization\Domain\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Organization\Domain\Enum\AccessRight;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use ValueError;


#[ORM\Entity]
#[ORM\Table(name: 'organization_groups')]
class Group
    implements OrganizationOwnedEntityInterface
{
    /**
     * @throws Exception
     * @param AccessRight[] $accessRights
     */
    public function __construct(
        Organization $organization,
        string       $name,
        array        $accessRights,
        bool         $isDefaultForNewMembers
    )
    {
        $this->organization = $organization;
        $this->name = $name;

        foreach ($accessRights as $accessRight) {
            if (!$accessRight instanceof AccessRight) {
                throw new ValueError('Not an access right in $accessRights.');
            }
        }
        $this->accessRights = $accessRights;

        $this->members = new ArrayCollection();

        $this->isDefaultForNewMembers = $isDefaultForNewMembers;

        $this->createdAt = DateAndTimeService::getDateTime();
    }


    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(type: Types::GUID, unique: true)]
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
    private readonly string $name;

    public function getName(): string
    {
        return $this->name;
    }

    #[ORM\Column(
        type: Types::DATETIME_MUTABLE,
        nullable: false
    )]
    private readonly DateTime $createdAt;

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }


    #[ORM\Column(
        type: Types::SIMPLE_ARRAY,
        length: 1024,
        nullable: true,
        enumType: AccessRight::class
    )]
    private readonly array $accessRights;

    /**
     * @return AccessRight[]
     */
    public function getAccessRights(): array
    {
        return $this->accessRights;
    }


    /**
     * @var Collection<int, User>
     */
    #[ORM\JoinTable(name: 'users_organization_groups')]
    #[ORM\JoinColumn(
        name: 'users_id',
        referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(
        name: 'organization_groups_id',
        referencedColumnName: 'id',
        unique: false
    )]
    #[ORM\ManyToMany(targetEntity: User::class)]
    private Collection $members;

    public function addMember(
        User $user
    ): void
    {
        /** @var User $member */
        foreach ($this->members as $member) {
            if ($member->getId() === $user->getId()) {
                throw new ValueError("User '{$user->getId()}' is already in group '{$this->getName()}'.");
            }
        }

        $this->members->add($user);
    }

    public function removeMember(
        User $user
    ): void
    {
        $this->members->removeElement($user);
    }

    /**
     * @return User[]
     */
    public function getMembers(): array
    {
        return $this->members->toArray();
    }


    #[ORM\Column(
        type: Types::BOOLEAN,
        nullable: false
    )]
    private readonly bool $isDefaultForNewMembers;

    public function isDefaultForNewMembers(): bool
    {
        return $this->isDefaultForNewMembers;
    }


    public function isAdministratorsGroup(): bool
    {
        return $this->getName() === 'Administrators';
    }

    public function isTeamMembersGroup(): bool
    {
        return $this->getName() === 'Team Members';
    }
}
