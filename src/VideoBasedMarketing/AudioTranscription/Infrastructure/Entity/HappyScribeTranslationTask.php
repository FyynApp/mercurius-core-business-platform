<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Enum\HappyScribeTranslationTaskState;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;


#[ORM\Entity]
#[ORM\Table(name: 'audio_transcription_happy_scribe_translation_tasks')]
#[ORM\Index(
    fields: ['createdAt'],
    name: 'created_at_idx'
)]
class HappyScribeTranslationTask
{
    /**
     * @throws Exception
     */
    public function __construct(
        HappyScribeTranscription        $happyScribeTranscription,
        string                          $happyScribeTranslationTaskId,
        HappyScribeTranslationTaskState $happyScribeTranslationTaskState,
        Bcp47LanguageCode               $bcp47LanguageCode
    )
    {
        $this->happyScribeTranscription = $happyScribeTranscription;
        $this->happyScribeTranslationTaskId = $happyScribeTranslationTaskId;
        $this->state = $happyScribeTranslationTaskState;
        $this->bcp47LanguageCode = $bcp47LanguageCode;
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
        targetEntity: HappyScribeTranscription::class,
        cascade: ['persist'],
    )]
    #[ORM\JoinColumn(
        name: 'audio_transcription_happy_scribe_transcriptions_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private HappyScribeTranscription $happyScribeTranscription;

    public function getHappyScribeTranscription(): HappyScribeTranscription
    {
        return $this->happyScribeTranscription;
    }


    #[ORM\Column(
        type: Types::STRING,
        length: 32,
        nullable: false,
        enumType: HappyScribeTranslationTaskState::class
    )]
    private HappyScribeTranslationTaskState $state;

    public function getState(): HappyScribeTranslationTaskState
    {
        return $this->state;
    }

    public function setState(
        HappyScribeTranslationTaskState $state
    ): void
    {
        $this->state = $state;
    }


    #[ORM\Column(
        type: Types::STRING,
        length: 16,
        nullable: false,
        enumType: Bcp47LanguageCode::class
    )]
    private Bcp47LanguageCode $bcp47LanguageCode;

    public function getBcp47LanguageCode(): Bcp47LanguageCode
    {
        return $this->bcp47LanguageCode;
    }


    #[ORM\Column(
        type: Types::STRING,
        length: 256,
        nullable: false,
    )]
    private string $happyScribeTranslationTaskId;

    public function getHappyScribeTranslationTaskId(): string
    {
        return $this->happyScribeTranslationTaskId;
    }


    #[ORM\Column(
        type: Types::STRING,
        length: 256,
        nullable: true
    )]
    private ?string $translatedTranscriptionId;

    public function getTranslatedTranscriptionId(): ?string
    {
        return $this->translatedTranscriptionId;
    }

    public function setTranslatedTranscriptionId(string $translatedTranscriptionId): void
    {
        $this->translatedTranscriptionId = $translatedTranscriptionId;
    }
}
