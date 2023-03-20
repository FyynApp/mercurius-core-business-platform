<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Entity;

use App\Shared\Infrastructure\Entity\SupportsShortIdInterface;
use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use App\VideoBasedMarketing\Organization\Domain\Entity\OrganizationOwnedEntityInterface;
use App\VideoBasedMarketing\Recordings\Infrastructure\Entity\RecordingSessionVideoChunk;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;


#[ORM\Entity]
#[ORM\Table(name: 'recording_sessions')]
#[ORM\Index(
    fields: ['createdAt'],
    name: 'created_at_idx'
)]
class RecordingSession
    implements UserOwnedEntityInterface, OrganizationOwnedEntityInterface, SupportsShortIdInterface
{
    /**
     * @throws Exception
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->organization = $user->getCurrentlyActiveOrganization();
        $this->createdAt = DateAndTimeService::getDateTime();
        $this->recordingSessionVideoChunks = new ArrayCollection();
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
        type: Types::STRING,
        length: 12,
        unique: true,
        nullable: true
    )]
    private ?string $shortId = null;

    public function setShortId(string $shortId): void
    {
        $this->shortId = $shortId;
    }

    public function getShortId(): ?string
    {
        return $this->shortId;
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


    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isDone = false;

    public function isDone(): bool
    {
        return $this->isDone;
    }

    public function setIsDone(bool $isDone): void
    {
        $this->isDone = $isDone;
    }


    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $recordingPreviewAssetHasBeenGenerated = false;

    public function hasRecordingPreviewAssetBeenGenerated(): bool
    {
        return $this->recordingPreviewAssetHasBeenGenerated;
    }

    public function setRecordingPreviewAssetHasBeenGenerated(bool $val): void
    {
        $this->recordingPreviewAssetHasBeenGenerated = $val;
    }


    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isFinished = false;

    public function isFinished(): bool
    {
        return $this->isFinished;
    }

    public function setIsFinished(bool $isFinished): void
    {
        $this->isFinished = $isFinished;
    }


    #[ORM\ManyToOne(
        targetEntity: User::class,
        cascade: ['persist'],
        inversedBy: 'recordingSessions'
    )]
    #[ORM\JoinColumn(
        name: 'users_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?User $user;

    public function getUser(): User
    {
        if (is_null($this->user)) {
            return $this->organization->getOwningUser();
        }
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }


    #[ORM\ManyToOne(
        targetEntity: Organization::class,
        cascade: ['persist']
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

    public function setOrganization(
        Organization $organization
    ): void
    {
        $this->organization = $organization;
    }


    /** @var RecordingSession[]|Collection */
    #[ORM\OneToMany(mappedBy: 'recordingSession', targetEntity: RecordingSessionVideoChunk::class, cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    private array|Collection $recordingSessionVideoChunks;

    /**
     * @return RecordingSessionVideoChunk[]|Collection
     */
    public function getRecordingSessionVideoChunks(): array|Collection
    {
        return $this->recordingSessionVideoChunks;
    }

    public function setRecordingSessionVideoChunks(Collection $recordingSessionVideoChunks): void
    {
        $this->recordingSessionVideoChunks = $recordingSessionVideoChunks;
    }


    #[ORM\OneToOne(mappedBy: 'recordingSession', targetEntity: Video::class, cascade: ['persist'])]
    private ?Video $video = null;

    public function getVideo(): ?Video
    {
        return $this->video;
    }

    public function setVideo(?Video $video): void
    {
        $this->video = $video;
    }
}
