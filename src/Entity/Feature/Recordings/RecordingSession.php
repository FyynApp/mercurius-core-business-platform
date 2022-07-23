<?php

namespace App\Entity\Feature\Recordings;

use App\Entity\Feature\Account\User;
use App\Service\Aspect\DateAndTime\DateAndTimeService;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;

#[ORM\Entity]
#[ORM\Table(name: 'recording_sessions', indexes: [])]
#[ORM\Index(name: "created_at_idx", fields: ['createdAt'])]
class RecordingSession
{
    public function __construct()
    {
        $this->createdAt = DateAndTimeService::getDateTimeUtc();
        $this->recordingSessionVideoChunks = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(type: 'guid', unique: true)]
    private string $id;

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

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'], inversedBy: 'recordingSessions')]
    #[ORM\JoinColumn(name: 'users_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }


    /** @var RecordingSession[]|Collection */
    #[ORM\OneToMany(mappedBy: 'recordingSession', targetEntity: RecordingSessionVideoChunk::class, cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    private Collection $recordingSessionVideoChunks;

    /**
     * @return RecordingSessionVideoChunk[]|Collection
     */
    public function getRecordingSessionVideoChunks(): Collection
    {
        return $this->recordingSessionVideoChunks;
    }


    #[ORM\OneToOne(mappedBy: 'recordingSession', targetEntity: RecordingSessionFullVideo::class, cascade: ['persist'])]
    private ?RecordingSessionFullVideo $recordingSessionFullVideo = null;

    public function getRecordingSessionFullVideo(): ?RecordingSessionFullVideo
    {
        return $this->recordingSessionFullVideo;
    }
}
