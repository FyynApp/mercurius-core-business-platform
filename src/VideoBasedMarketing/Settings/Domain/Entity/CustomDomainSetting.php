<?php

namespace App\VideoBasedMarketing\Settings\Domain\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Settings\Domain\Enum\DomainCheckStatus;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;

#[ORM\Entity]
#[ORM\Table(name: 'custom_domain_settings')]
#[ORM\Index(fields: ['createdAt'], name: 'created_at_idx')]
class CustomDomainSetting
{
    /**
     * @throws Exception
     */
    public function __construct(
        User $user
    )
    {
        $this->user = $user;
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


    #[ORM\OneToOne(
        inversedBy: 'customDomainSetting',
        targetEntity: User::class,
        cascade: ['persist']
    )]
    #[ORM\JoinColumn(
        name: 'users_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private User $user;

    public function getuser(): User
    {
        return $this->user;
    }


    #[ORM\Column(
        type: 'string',
        length: 256,
        unique: false,
        nullable: true
    )]
    private ?string $domainName = null;

    public function setDomainName(
        ?string $domainName
    ): void
    {
        $this->domainName = $domainName;
    }

    public function getDomainName(): ?string
    {
        return $this->domainName;
    }


    #[ORM\Column(
        type: 'smallint',
        nullable: false,
        enumType: DomainCheckStatus::class
    )]
    private DomainCheckStatus $checkStatus;

    public function setCheckStatus(
        DomainCheckStatus $checkStatus
    ): void
    {
        $this->checkStatus = $checkStatus;
    }

    public function getCheckStatus(): DomainCheckStatus
    {
        return $this->checkStatus;
    }


    #[ORM\Column(type: 'datetime', nullable: false)]
    private DateTime $createdAt;

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }
}
