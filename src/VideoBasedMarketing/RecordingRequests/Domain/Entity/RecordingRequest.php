<?php

namespace App\VideoBasedMarketing\RecordingRequests\Domain\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
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
    implements UserOwnedEntityInterface
{
    /**
     * @throws Exception
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->videos = new ArrayCollection();
        $this->respondingUsers = new ArrayCollection();
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
        mappedBy: 'respondingToRecordingRequest',
        targetEntity: User::class,
        cascade: ['persist']
    )]
    private array|Collection $respondingUsers;

    /** @return User[]|Collection */
    public function getRespondingUsers(): array|Collection
    {
        return $this->respondingUsers;
    }

    public function addRespondingUser(User $user): void
    {
        $this->respondingUsers->add($user);
    }


    #[ORM\OneToMany(
        mappedBy: 'recordingRequest',
        targetEntity: Video::class,
        cascade: ['persist']
    )]
    private array|Collection $videos;

    public function getVideos(): array|Collection
    {
        return $this->videos;
    }

    public function addVideo(Video $video): void
    {
        $this->videos->add($video);
    }
}
