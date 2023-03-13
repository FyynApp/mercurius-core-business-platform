<?php

namespace App\VideoBasedMarketing\AudioTranscription\Domain\Entity;

use App\VideoBasedMarketing\AudioTranscription\Domain\Enum\AudioTranscriptionBcp47LanguageCode;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;


#[ORM\Entity]
#[ORM\Table(name: 'audio_transcription_words')]
#[ORM\Index(
    fields: ['audioTranscription', 'audioTranscriptionBcp47LanguageCode', 'speaker', 'dataStart'],
    name: 'main_idx'
)]
#[ORM\Index(
    fields: ['word'],
    name: 'word_idx',
)]
class AudioTranscriptionWord
{
    /**
     * @throws Exception
     */
    public function __construct(
        AudioTranscription                  $audioTranscription,
        AudioTranscriptionBcp47LanguageCode $audioTranscriptionBcp47LanguageCode,
        string                              $speaker,
        ?int                                $speakerNumber,
        string                              $word,
        string                              $wordType,
        float                               $dataStart,
        float                               $dataEnd,
        float                               $confidence
    )
    {
        $this->audioTranscription = $audioTranscription;
        $this->audioTranscriptionBcp47LanguageCode = $audioTranscriptionBcp47LanguageCode;
        $this->speaker = $speaker;
        $this->speakerNumber = $speakerNumber;
        $this->word = $word;
        $this->wordType = $wordType;
        $this->dataStart = $dataStart;
        $this->dataEnd = $dataEnd;
        $this->confidence = $confidence;
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
        length: 16,
        nullable: false,
        enumType: AudioTranscriptionBcp47LanguageCode::class
    )]
    private AudioTranscriptionBcp47LanguageCode $audioTranscriptionBcp47LanguageCode;

    public function getAudioTranscriptionBcp47LanguageCode(): AudioTranscriptionBcp47LanguageCode
    {
        return $this->audioTranscriptionBcp47LanguageCode;
    }

    public function setAudioTranscriptionBcp47LanguageCode(
        AudioTranscriptionBcp47LanguageCode $audioTranscriptionBcp47LanguageCode
    ): void
    {
        $this->audioTranscriptionBcp47LanguageCode = $audioTranscriptionBcp47LanguageCode;
    }


    public function isOriginalLanguage(): bool
    {
        return $this->audioTranscriptionBcp47LanguageCode
            === $this->audioTranscription->getOriginalLanguageBcp47LanguageCode();
    }


    #[ORM\Column(
        type: Types::STRING,
        length: 256,
        nullable: false
    )]
    private string $speaker;

    public function getSpeaker(): string
    {
        return $this->speaker;
    }


    #[ORM\Column(
        type: Types::INTEGER,
        nullable: true
    )]
    private string $speakerNumber;

    public function getSpeakerNumber(): ?int
    {
        return $this->speakerNumber;
    }


    #[ORM\Column(
        type: Types::STRING,
        length: 256,
        nullable: false
    )]
    private string $word;

    public function getWord(): string
    {
        return $this->word;
    }


    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        nullable: false
    )]
    private string $wordType;

    public function getWordType(): string
    {
        return $this->wordType;
    }


    #[ORM\Column(
        type: Types::FLOAT,
        nullable: false
    )]
    private string $dataStart;

    public function getDataStart(): string
    {
        return $this->dataStart;
    }


    #[ORM\Column(
        type: Types::FLOAT,
        nullable: false
    )]
    private string $dataEnd;

    public function getDataEnd(): string
    {
        return $this->dataEnd;
    }


    #[ORM\Column(
        type: Types::FLOAT,
        nullable: false
    )]
    private string $confidence;

    public function getConfidence(): string
    {
        return $this->confidence;
    }
}
