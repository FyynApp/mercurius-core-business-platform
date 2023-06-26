<?php

namespace App\VideoBasedMarketing\Membership\Domain\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
use App\VideoBasedMarketing\Membership\Domain\Enum\PackageName;
use App\VideoBasedMarketing\Membership\Domain\Enum\PurchaseStatus;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;


#[ORM\Entity]
#[ORM\Table(name: 'purchases')]
class Purchase
    implements UserOwnedEntityInterface
{
    /**
     * @throws Exception
     */
    public function __construct(
        #[ORM\ManyToOne(
            targetEntity: User::class,
            cascade: ['persist']
        )]
        #[ORM\JoinColumn(
            name: 'users_id',
            referencedColumnName: 'id',
            nullable: false,
            onDelete: 'CASCADE'
        )]
        private readonly User        $user,

        #[ORM\Column(
            type: Types::STRING,
            nullable: false,
            enumType: PackageName::class
        )]
        private readonly PackageName $packageName,

        #[ORM\Column(
            type: Types::STRING,
            nullable: false,
            enumType: PurchaseStatus::class
        )]
        private PurchaseStatus       $status
    )
    {
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
        type: Types::DATETIME_MUTABLE,
        nullable: false
    )]
    private DateTime $createdAt;

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }


    public function getUser(): User
    {
        return $this->user;
    }


    public function getPackageName(): PackageName
    {
        return $this->packageName;
    }


    public function getStatus(): PurchaseStatus
    {
        return $this->status;
    }

    public function setStatus(
        PurchaseStatus $status
    ): void
    {
        $this->status = $status;
    }
}
