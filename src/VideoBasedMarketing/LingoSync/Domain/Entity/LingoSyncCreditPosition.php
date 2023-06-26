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
        ?Subscription     $subscription = null,
        ?Purchase         $purchase = null,
        ?LingoSyncProcess $lingoSyncProcess = null
    )
    {
        $notNullCount = 0;
        foreach ([$subscription, $purchase, $lingoSyncProcess] as $object) {
            if (!is_null($object)) {
                $notNullCount++;
            }
        }

        if ($notNullCount !== 1) {
            throw new ValueError('Exactly one of $subscription, $purchase or $lingoSyncProcess must not be null.');
        }

        // if we received a $subscription or a $purchase, then $amount must be positive,
        // and if we received a $lingoSyncProcess, then $amount must be negative
        if (   ($amount < 0 && !is_null($subscription))
            || ($amount < 0 && !is_null($purchase))
            || ($amount > 0 && !is_null($lingoSyncProcess))
        ) {
            throw new ValueError('Invalid combination of $amount and $subscription, $purchase or $lingoSyncProcess.');
        }

        $this->subscription = $subscription;
        $this->purchase = $purchase;
        $this->lingoSyncProcess = $lingoSyncProcess;

        $this->createdAt = DateAndTimeService::getDateTimeImmutable();
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
        nullable: false,
        options: ['unsigned' => true]
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
        name: 'subscriptions_id',
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
        name: 'purchases_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'CASCADE'
    )]
    private readonly ?Purchase $purchase;

    public function getPurchase(): Purchase
    {
        return $this->purchase;
    }


    #[ORM\ManyToOne(
        targetEntity: LingoSyncProcess::class,
        cascade: ['persist']
    )]
    #[ORM\JoinColumn(
        name: 'lingosync_processes_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'CASCADE'
    )]
    private readonly ?LingoSyncProcess $lingoSyncProcess;

    public function getLingoSyncProcess(): LingoSyncProcess
    {
        return $this->lingoSyncProcess;
    }


    public function getUser(): User
    {
        if (!is_null($this->subscription)) {
            return $this->subscription->getUser();
        }

        return $this->purchase->getUser();
    }
}
