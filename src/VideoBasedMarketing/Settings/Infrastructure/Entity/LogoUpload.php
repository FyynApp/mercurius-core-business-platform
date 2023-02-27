<?php

namespace App\VideoBasedMarketing\Settings\Infrastructure\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use App\VideoBasedMarketing\Organization\Domain\Entity\OrganizationOwnedEntityInterface;
use App\VideoBasedMarketing\Settings\Domain\Entity\CustomLogoSetting;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;


#[ORM\Entity]
#[ORM\Table(name: 'settings_logo_uploads')]
#[ORM\Index(
    fields: ['createdAt'],
    name: 'created_at_idx'
)]
class LogoUpload
    implements OrganizationOwnedEntityInterface
{
    /**
     * @throws Exception
     */
    public function __construct(
        Organization $organization,
        string       $tusToken,
        string       $fileName,
        string       $fileType,
    )
    {
        $this->organization = $organization;
        $this->tusToken = $tusToken;
        $this->fileName = $fileName;
        $this->fileType = $fileType;
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


    #[ORM\ManyToOne(
        targetEntity: Organization::class,
        cascade: ['persist'],
        inversedBy: 'logoUploads'
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

    public function setOrganization(Organization $organization): void
    {
        $this->organization = $organization;
    }

    #[ORM\Column(
        type: Types::GUID,
        unique: true
    )]
    private string $tusToken;

    public function getTusToken(): string
    {
        return $this->tusToken;
    }


    #[ORM\Column(
        type: Types::STRING,
        length: 256,
        unique: false,
        nullable: false
    )]
    private string $fileName;

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }


    #[ORM\Column(
        type: Types::STRING,
        length: 32,
        unique: false,
        nullable: false
    )]
    private string $fileType;

    public function getFileType(): string
    {
        return $this->fileType;
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


    #[ORM\OneToOne(
        mappedBy: 'logoUpload',
        targetEntity: CustomLogoSetting::class,
        cascade: ['persist']
    )]
    #[ORM\JoinColumn(
        name: 'custom_logo_settings_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?CustomLogoSetting $customLogoSetting;

    public function getCustomLogoSetting(): ?CustomLogoSetting
    {
        return $this->customLogoSetting;
    }

    public function setCustomLogoSetting(
        ?CustomLogoSetting $customLogoSetting
    ): void
    {
        $this->customLogoSetting = $customLogoSetting;
    }
}
