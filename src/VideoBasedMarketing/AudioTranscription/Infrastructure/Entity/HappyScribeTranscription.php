<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscription;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Enum\HappyScribeTranscriptionState;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;


#[ORM\Entity]
#[ORM\Table(name: 'audio_transcription_happy_scribe_transcriptions')]
#[ORM\Index(
    fields: ['createdAt'],
    name: 'created_at_idx'
)]
class HappyScribeTranscription
{
    /**
     * @throws Exception
     */
    public function __construct(
        AudioTranscription            $audioTranscription,
        string                        $happyScribeTranscriptionId,
        HappyScribeTranscriptionState $happyScribeTranscriptionState
    )
    {
        $this->audioTranscription = $audioTranscription;
        $this->happyScribeTranscriptionId = $happyScribeTranscriptionId;
        $this->state = $happyScribeTranscriptionState;
        $this->createdAt = DateAndTimeService::getDateTime();
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
        type: Types::DATETIME_MUTABLE,
        nullable: false
    )]
    private DateTime $createdAt;

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }


    #[ORM\ManyToOne(
        targetEntity: AudioTranscription::class,
        cascade: ['persist'],
    )]
    #[ORM\JoinColumn(
        name: 'audio_transcriptions_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private AudioTranscription $audioTranscription;

    public function getAudioTranscription(): AudioTranscription
    {
        return $this->audioTranscription;
    }


    #[ORM\Column(
        type: Types::STRING,
        nullable: false,
        enumType: HappyScribeTranscriptionState::class
    )]
    private HappyScribeTranscriptionState $state;

    public function getState(): HappyScribeTranscriptionState
    {
        return $this->state;
    }

    public function setState(
        HappyScribeTranscriptionState $state
    ): void
    {
        $this->state = $state;
    }


    #[ORM\Column(
        type: Types::STRING,
        nullable: false,
    )]
    private string $happyScribeTranscriptionId;

    public function getHappyScribeTranscriptionId(): string
    {
        return $this->happyScribeTranscriptionId;
    }
}
