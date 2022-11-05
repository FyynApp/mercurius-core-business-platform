<?php

namespace App\VideoBasedMarketing\Membership\Domain\Entity;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\Service\Aspect\DateAndTime\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;


#[ORM\Entity]
#[ORM\Table(name: 'subscriptions')]
class Subscription
    implements UserOwnedEntityInterface
{
    public function __construct(
        User $user,
        MembershipPlan $membershipPlan,
        SubscriptionStatus $status
    )
    {
        $this->createdAt = DateAndTimeService::getDateTimeUtc();
        $this->user = $user;
        $this->membershipPlanName = $membershipPlan->getName();
        $this->status = $status;
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


    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'], inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(name: 'users_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    public function getUser(): User
    {
        return $this->user;
    }


    #[ORM\Column(type: 'string', nullable: false, enumType: MembershipPlanName::class)]
    private MembershipPlanName $membershipPlanName;

    public function getMembershipPlanName(): MembershipPlanName
    {
        return $this->membershipPlanName;
    }

    public function setMembershipPlanName(MembershipPlanName $membershipPlanName): void
    {
        $this->membershipPlanName = $membershipPlanName;
    }


    #[ORM\Column(type: 'string', nullable: false, enumType: SubscriptionStatus::class)]
    private SubscriptionStatus $status;

    public function getStatus(): SubscriptionStatus
    {
        return $this->status;
    }

    public function setStatus(SubscriptionStatus $status): void
    {
        $this->status = $status;
    }
}
