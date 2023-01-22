<?php

namespace App\VideoBasedMarketing\Membership\Domain\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
use App\VideoBasedMarketing\Membership\Domain\Enum\MembershipPlanName;
use App\VideoBasedMarketing\Membership\Domain\Enum\SubscriptionStatus;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;


#[ORM\Entity]
#[ORM\Table(name: 'subscriptions')]
class Subscription
    implements UserOwnedEntityInterface
{
    /**
     * @throws Exception
     */
    public function __construct(

        #[ORM\ManyToOne(
            targetEntity: User::class,
            cascade: ['persist'],
            inversedBy: 'subscriptions'
        )]
        #[ORM\JoinColumn(
            name: 'users_id',
            referencedColumnName: 'id',
            nullable: false,
            onDelete: 'CASCADE'
        )]
        private readonly User $user,

        #[ORM\Column(
            type: 'string',
            nullable: false,
            enumType: MembershipPlanName::class
        )]
        private readonly MembershipPlanName $membershipPlanName,

        #[ORM\Column(
            type: 'string',
            nullable: false,
            enumType: SubscriptionStatus::class
        )]
        private SubscriptionStatus $status
    )
    {
        $this->createdAt = DateAndTimeService::getDateTime();
    }


    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(type: 'guid', unique: true)]
    private ?string $id = null;

    public function getId(): ?string
    {
        return $this->id;
    }


    #[ORM\Column(type: 'datetime', nullable: false)]
    private DateTime $createdAt;

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }


    public function getUser(): User
    {
        return $this->user;
    }


    public function getMembershipPlanName(): MembershipPlanName
    {
        return $this->membershipPlanName;
    }


    public function getStatus(): SubscriptionStatus
    {
        return $this->status;
    }

    public function setStatus(SubscriptionStatus $status): void
    {
        $this->status = $status;
    }
}
