<?php

namespace App\VideoBasedMarketing\Settings\Domain\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
use App\VideoBasedMarketing\Settings\Domain\Enum\CustomDomainDnsSetupStatus;
use App\VideoBasedMarketing\Settings\Domain\Enum\CustomDomainHttpSetupStatus;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;

#[ORM\Entity]
#[ORM\Table(name: 'custom_domain_settings')]
#[ORM\Index(fields: ['createdAt'], name: 'created_at_idx')]
class CustomDomainSetting
    implements UserOwnedEntityInterface
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

    public function getUser(): User
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
        enumType: CustomDomainDnsSetupStatus::class
    )]
    private CustomDomainDnsSetupStatus $dnsSetupStatus = CustomDomainDnsSetupStatus::CheckOutstanding;

    public function setDnsSetupStatus(
        CustomDomainDnsSetupStatus $dnsSetupStatus
    ): void
    {
        $this->dnsSetupStatus = $dnsSetupStatus;
    }

    public function getDnsSetupStatus(): CustomDomainDnsSetupStatus
    {
        return $this->dnsSetupStatus;
    }


    #[ORM\Column(
        type: 'smallint',
        nullable: false,
        enumType: CustomDomainHttpSetupStatus::class
    )]
    private CustomDomainHttpSetupStatus $httpSetupStatus = CustomDomainHttpSetupStatus::CheckOutstanding;

    public function setHttpSetupStatus(
        CustomDomainHttpSetupStatus $httpSetupStatus
    ): void
    {
        $this->httpSetupStatus = $httpSetupStatus;
    }

    public function getHttpSetupStatus(): CustomDomainHttpSetupStatus
    {
        return $this->httpSetupStatus;
    }


    #[ORM\Column(type: 'datetime', nullable: false)]
    private DateTime $createdAt;

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }
}
