<?php

namespace App\Entity\Feature\Account;

use App\Entity\Feature\Recordings\RecordingSession;
use App\Entity\Feature\Recordings\RecordingSettingsBag;
use App\Entity\Feature\Recordings\Video;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;

#[ORM\Entity]
#[ORM\Table(name: 'unregistered_client')]
class UnregisteredClient
{
    public function __construct()
    {
        $this->recordingSessions = new ArrayCollection();
        $this->recordingSettingsBags = new ArrayCollection();
        $this->videos = new ArrayCollection();
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


    #[ORM\Column(type: 'string', length: 180, unique: false, nullable: true)]
    private ?string $email;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }


    /** @var RecordingSession[]|Collection */
    #[ORM\OneToMany(mappedBy: 'unregistered_client', targetEntity: RecordingSession::class, cascade: ['persist'])]
    private array|Collection $recordingSessions;

    /**
     * @return RecordingSession[]|Collection
     */
    public function getRecordingSessions(): array|Collection
    {
        return $this->recordingSessions;
    }


    /** @var RecordingSettingsBag[]|Collection */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: RecordingSettingsBag::class, cascade: ['persist'])]
    private array|Collection $recordingSettingsBags;


    /** @var Video[]|Collection */
    #[ORM\OneToMany(mappedBy: 'unregistered_client', targetEntity: Video::class, cascade: ['persist'])]
    private array|Collection $videos;

    /**
     * @return Video[]|Collection
     */
    public function getVideos(): array|Collection
    {
        return $this->videos;
    }
}
