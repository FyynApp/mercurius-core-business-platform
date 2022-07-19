<?php

namespace App\Entity\Feature\Recordings;

use App\Entity\Feature\Presentationpage\Presentationpage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;

#[ORM\Entity]
#[ORM\Table(name: 'recording_session_full_videos')]
class RecordingSessionFullVideo
{
    public function __construct()
    {
        $this->presentationpages = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    public function getId(): ?string
    {
        return $this->id;
    }


    #[ORM\OneToOne(inversedBy: 'recordingSessionFullVideo', targetEntity: RecordingSession::class, cascade: ['persist'])]
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


    #[ORM\Column(type: 'blob', unique: false, nullable: false)]
    /** @var resource */
    private $videoBlob;

    /** @return resource */
    public function getVideoBlob()
    {
        return $this->videoBlob;
    }

    public function setVideoBlob(mixed $videoBlob): void
    {
        $this->videoBlob = $videoBlob;
    }


    #[ORM\Column(type: 'blob', unique: false, nullable: false)]
    /** @var resource */
    private $previewImageBlob;

    /** @return resource */
    public function getPreviewImageBlob()
    {
        return $this->previewImageBlob;
    }

    public function setPreviewImageBlob(mixed $previewImageBlob): void
    {
        $this->previewImageBlob = $previewImageBlob;
    }


    /** @var Presentationpage[]|Collection */
    #[ORM\OneToMany(mappedBy: 'recordingSessionFullVideo', targetEntity: Presentationpage::class, cascade: ['persist'])]
    private Collection $presentationpages;

    /**
     * @return Presentationpage[]|Collection
     */
    public function getPresentationpages(): Collection
    {
        return $this->presentationpages;
    }
}
