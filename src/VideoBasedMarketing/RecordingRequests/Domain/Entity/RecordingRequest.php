<?php

namespace App\VideoBasedMarketing\RecordingRequests\Domain\Entity;

use App\Shared\Infrastructure\Entity\SupportsShortIdInterface;
use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;


#[ORM\Entity]
#[ORM\Table(
    name: 'recording_requests',
    indexes: []
)]
#[ORM\Index(
    fields: ['createdAt'],
    name: 'created_at_idx'
)]
class RecordingRequest
    implements UserOwnedEntityInterface, SupportsShortIdInterface
{
    /**
     * @throws Exception
     */
    public function __construct(
        User $user
    )
    {
        $this->user = $user;
        $this->responses = new ArrayCollection();
        $this->createdAt = DateAndTimeService::getDateTimeUtc();
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


    #[ORM\Column(type: 'string', length: 12, unique: true, nullable: true)]
    private ?string $shortId = null;

    public function setShortId(string $shortId): void
    {
        $this->shortId = $shortId;
    }

    public function getShortId(): ?string
    {
        return $this->shortId;
    }


    #[ORM\ManyToOne(
        targetEntity: User::class,
        cascade: ['persist'],
        inversedBy: 'recordingRequests'
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
        type: 'datetime',
        nullable: false
    )]
    private DateTime $createdAt;

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }


    #[ORM\OneToMany(
        mappedBy: 'request',
        targetEntity: RecordingRequestResponse::class,
        cascade: ['persist']
    )]
    private array|Collection $responses;

    /** @return RecordingRequestResponse[]|Collection */
    public function getResponses(): array|Collection
    {
        return $this->responses;
    }

    public function addResponse(
        RecordingRequestResponse $response
    ): void
    {
        $this->responses->add($response);
    }
}
