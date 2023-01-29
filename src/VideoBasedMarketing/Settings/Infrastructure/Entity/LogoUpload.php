<?php

namespace App\VideoBasedMarketing\Settings\Infrastructure\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
use App\VideoBasedMarketing\Settings\Domain\Entity\CustomLogoSetting;
use DateTime;
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
    implements UserOwnedEntityInterface
{
    /**
     * @throws Exception
     */
    public function __construct(
        User   $user,
        string $tusToken,
        string $fileName,
        string $fileType,
    )
    {
        $this->user = $user;
        $this->tusToken = $tusToken;
        $this->fileName = $fileName;
        $this->fileType = $fileType;
        $this->createdAt = DateAndTimeService::getDateTime();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(
        type: 'guid',
        unique: true
    )]
    private ?string $id = null;

    public function getId(): ?string
    {
        return $this->id;
    }


    #[ORM\ManyToOne(
        targetEntity: User::class,
        cascade: ['persist'],
        inversedBy: 'logoUploads'
    )]
    #[ORM\JoinColumn(
        name: 'users_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private User $user;

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    #[ORM\Column(
        type: 'guid',
        unique: true
    )]
    private string $tusToken;

    public function getTusToken(): string
    {
        return $this->tusToken;
    }


    #[ORM\Column(
        type: 'string',
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
        type: 'string',
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
        type: 'datetime',
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
