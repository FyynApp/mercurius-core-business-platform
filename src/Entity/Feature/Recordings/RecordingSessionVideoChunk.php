<?php

namespace App\Entity\Feature\Recordings;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;


#[ORM\Entity]
#[ORM\Table(name: 'recording_session_video_chunks')]
#[ORM\UniqueConstraint(name: 'session_name', columns: ['recording_sessions_id', 'name'])]
#[ORM\Index(fields: ['createdAt'], name: 'created_at_idx')]
class RecordingSessionVideoChunk
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(type: 'guid', unique: true)]
    private ?string $id = null;

    public function getId(): ?string
    {
        return $this->id;
    }


    #[ORM\ManyToOne(targetEntity: RecordingSession::class, cascade: ['persist'], inversedBy: 'recordingSessionVideoChunks')]
    #[ORM\JoinColumn(name: 'recording_sessions_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private RecordingSession $recordingSession;

    public function getRecordingSession(): RecordingSession
    {
        return $this->recordingSession;
    }

    public function setRecordingSession(RecordingSession $recordingSession): void
    {
        $this->recordingSession = $recordingSession;
    }


    #[ORM\Column(type: 'string', length: 256, unique: false, nullable: false)]
    private string $name;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }


    #[ORM\Column(type: 'string', length: 32, unique: false, nullable: false)]
    private string $mimeType;

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }


    #[ORM\Column(type: 'datetime', nullable: false)]
    private DateTime $createdAt;

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $dateTime): void
    {
        $this->createdAt = $dateTime;
    }
}
