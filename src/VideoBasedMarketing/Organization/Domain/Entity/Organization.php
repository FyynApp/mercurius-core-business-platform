<?php

namespace App\VideoBasedMarketing\Organization\Domain\Entity;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Settings\Domain\Entity\CustomDomainSetting;
use App\VideoBasedMarketing\Settings\Domain\Entity\CustomLogoSetting;
use App\VideoBasedMarketing\Settings\Infrastructure\Entity\LogoUpload;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;


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
        type: Types::STRING,
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


    #[ORM\OneToOne(
        mappedBy: 'organization',
        targetEntity: CustomLogoSetting::class,
        cascade: ['persist']
    )]
    private ?CustomLogoSetting $customLogoSetting = null;

    public function getCustomLogoSetting(): ?CustomLogoSetting
    {
        return $this->customLogoSetting;
    }


    /** @var LogoUpload[]|Collection */
    #[ORM\OneToMany(
        mappedBy: 'organization',
        targetEntity: LogoUpload::class,
        cascade: ['persist']
    )]
    private array|Collection $logoUploads;

    /**
     * @return LogoUpload[]|Collection
     */
    public function getLogoUploads(): array|Collection
    {
        return $this->logoUploads;
    }
    

    #[ORM\OneToOne(
        mappedBy: 'organization',
        targetEntity: CustomDomainSetting::class,
        cascade: ['persist']
    )]
    private ?CustomDomainSetting $customDomainSetting = null;

    public function getCustomDomainSetting(): ?CustomDomainSetting
    {
        return $this->customDomainSetting;
    }
}
