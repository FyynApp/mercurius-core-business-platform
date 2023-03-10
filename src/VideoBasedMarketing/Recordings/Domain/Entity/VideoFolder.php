<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use App\VideoBasedMarketing\Organization\Domain\Entity\OrganizationOwnedEntityInterface;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;


#[ORM\Entity]
#[ORM\Table(name: 'video_folders')]
#[ORM\Index(
    fields: ['organization', 'name'],
    name: 'organizations_id_name_idx'
)]
class VideoFolder
    implements UserOwnedEntityInterface, OrganizationOwnedEntityInterface
{
    /**
     * @throws Exception
     */
    public function __construct(
        User   $user,
        string $name = ''
    )
    {
        $this->user = $user;
        $this->organization = $user->getCurrentlyActiveOrganization();
        $this->name = mb_substr(trim($name), 0, 265);
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

    public function getId(): ?string
    {
        return $this->id;
    }

    #[ORM\Column(
        type: Types::STRING,
        length: 256,
        unique: false,
        nullable: false
    )]
    private string $name;

    public function setName(string $name): void
    {
        $this->name = mb_substr(trim($name), 0, 265);
    }

    public function getName(): string
    {
        return $this->name;
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


    #[ORM\ManyToOne(
        targetEntity: User::class,
        cascade: ['persist'],
        inversedBy: 'videos'
    )]
    #[ORM\JoinColumn(
        name: 'users_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?User $user;

    public function getUser(): User
    {
        if (is_null($this->user)) {
            return $this->organization->getOwningUser();
        }
        return $this->user;
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
    private Organization $organization;

    public function getOrganization(): Organization
    {
        return $this->organization;
    }


    #[ORM\ManyToOne(
        targetEntity: self::class,
        cascade: ['persist']
    )]
    #[ORM\JoinColumn(
        name: 'parent_video_folders_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'CASCADE'
    )]
    private ?self $parentVideoFolder;

    public function getParentVideoFolder(): ?self
    {
        return $this->parentVideoFolder;
    }

    public function setParentVideoFolder(
        ?VideoFolder $parentVideoFolder
    ): void
    {
        $this->parentVideoFolder = $parentVideoFolder;
    }
}
