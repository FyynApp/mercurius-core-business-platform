<?php

namespace App\VideoBasedMarketing\AudioTranscription\Domain\Entity;

use App\VideoBasedMarketing\AudioTranscription\Domain\Enum\AudioTranscriptionBcp47LanguageCode;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;


#[ORM\Entity]
#[ORM\Table(name: 'audio_transcription_suggested_summaries')]
#[ORM\Index(
    fields: ['audioTranscription', 'audioTranscriptionBcp47LanguageCode'],
    name: 'main_idx'
)]
class AudioTranscriptionSuggestedSummary
{
    /**
     * @throws Exception
     */
    public function __construct(
        AudioTranscription                  $audioTranscription,
        AudioTranscriptionBcp47LanguageCode $audioTranscriptionBcp47LanguageCode
    )
    {
        $this->audioTranscription = $audioTranscription;
        $this->audioTranscriptionBcp47LanguageCode = $audioTranscriptionBcp47LanguageCode;
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
        type: Types::TEXT,
        nullable: false
    )]
    private string $summaryContent;

    public function getSummaryContent(): string
    {
        return $this->summaryContent;
    }
}
