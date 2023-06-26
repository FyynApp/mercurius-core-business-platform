<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
use App\VideoBasedMarketing\Membership\Domain\Entity\Purchase;
use App\VideoBasedMarketing\Membership\Domain\Entity\Subscription;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use ValueError;


#[ORM\Entity]
#[ORM\Table(name: 'lingosync_credit_positions')]
#[ORM\Index(
    fields: ['createdAt'],
    name: 'created_at_idx'
)]
class LingoSyncCreditPosition
    implements UserOwnedEntityInterface
{
    /**
     * @throws Exception
     */
    public function __construct(
        int               $amount,
        ?Subscription     $causingSubscription = null,
        ?Purchase         $causingPurchase = null,
        ?LingoSyncProcess $causingLingoSyncProcess = null,
        ?User             $causingUser = null
    )
    {
        $notNullCount = 0;
        foreach ([$causingSubscription, $causingPurchase, $causingLingoSyncProcess, $causingUser] as $object) {
            if (!is_null($object)) {
                $notNullCount++;
            }
        }

        if ($notNullCount !== 1) {
            throw new ValueError('Exactly one of $causingSubscription, $causingPurchase, $causingLingoSyncProcess or $causingUser must be provided.');
        }

        if (   ($amount < 0 && !is_null($causingSubscription))
            || ($amount < 0 && !is_null($causingPurchase))
            || ($amount < 0 && !is_null($causingUser))
            || ($amount > 0 && !is_null($causingLingoSyncProcess))
        ) {
            throw new ValueError('The amount must be positive for a LingoSyncProcess and negative for a Subscription, Purchase or User.');
        }

        $this->subscription = $causingSubscription;
        $this->purchase = $causingPurchase;
        $this->lingoSyncProcess = $causingLingoSyncProcess;
        $this->causingUser = $causingUser;

        $this->amount = $amount;
        $this->createdAt = DateAndTimeService::getDateTimeImmutable();

        $owningUser = null;

        if (!is_null($causingUser)) {
            $owningUser = $causingUser;
        } elseif (!is_null($causingSubscription)) {
            $owningUser = $causingSubscription->getUser();
        } elseif (!is_null($causingPurchase)) {
            $owningUser = $causingPurchase->getUser();
        } elseif (!is_null($causingLingoSyncProcess)) {
            $owningUser = $causingLingoSyncProcess->getVideo()->getOrganization()->getOwningUser();
        }

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

    public function getId(): ?string
    {
        return $this->id;
    }


    #[ORM\Column(
        type: Types::DATETIME_IMMUTABLE,
        nullable: false
    )]
    private readonly DateTimeImmutable $createdAt;

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }


    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false
    )]
    private readonly int $amount;

    public function getAmount(): int
    {
        return $this->amount;
    }


    #[ORM\ManyToOne(
        targetEntity: Subscription::class,
        cascade: ['persist']
    )]
    #[ORM\JoinColumn(
        name: 'causing_subscriptions_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'CASCADE'
    )]
    private readonly ?Subscription $subscription;

    public function getSubscription(): Subscription
    {
        return $this->subscription;
    }


    #[ORM\ManyToOne(
        targetEntity: Purchase::class,
        cascade: ['persist']
    )]
    #[ORM\JoinColumn(
        name: 'causing_purchases_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'CASCADE'
    )]
    private readonly ?Purchase $purchase;

    public function getPurchase(): ?Purchase
    {
        return $this->purchase;
    }


    #[ORM\ManyToOne(
        targetEntity: LingoSyncProcess::class,
        cascade: ['persist']
    )]
    #[ORM\JoinColumn(
        name: 'causing_lingosync_processes_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'CASCADE'
    )]
    private readonly ?LingoSyncProcess $lingoSyncProcess;

    public function getLingoSyncProcess(): ?LingoSyncProcess
    {
        return $this->lingoSyncProcess;
    }


    #[ORM\ManyToOne(
        targetEntity: User::class,
        cascade: ['persist']
    )]
    #[ORM\JoinColumn(
        name: 'causing_users_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'CASCADE'
    )]
    private readonly ?User $causingUser;

    public function getCausingUser(): ?User
    {
        return $this->causingUser;
    }


    #[ORM\ManyToOne(
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


    public function getUser(): User
    {
        return $this->owningUser;
    }
}
