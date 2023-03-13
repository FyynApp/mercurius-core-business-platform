<?php

namespace App\VideoBasedMarketing\AudioTranscription\Domain\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\AudioTranscription\Domain\Enum\AudioTranscriptionBcp47LanguageCode;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Entity\HappyScribeTranscription;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use ValueError;


#[ORM\Entity]
#[ORM\Table(name: 'audio_transcriptions')]
#[ORM\Index(
    fields: ['createdAt'],
    name: 'created_at_idx'
)]
#[ORM\Index(
    fields: ['webVtt'],
    name: 'web_vtt_fulltext_idx',
    flags: ['fulltext']
)]
#[ORM\Index(
    fields: ['suggestedSummary'],
    name: 'suggested_summary_fulltext_idx',
    flags: ['fulltext']
)]
class AudioTranscription
{
    /**
     * @throws Exception
     */
    public function __construct(
        Video                               $video,
        AudioTranscriptionBcp47LanguageCode $originalLanguageBcp47LanguageCode
    )
    {
        $this->video = $video;
        $this->originalLanguageBcp47LanguageCode = $originalLanguageBcp47LanguageCode;
        $this->createdAt = DateAndTimeService::getDateTime();
        $this->happyScribeTranscriptions = new ArrayCollection();
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
        targetEntity: Video::class,
        cascade: ['persist'],
    )]
    #[ORM\JoinColumn(
        name: 'videos_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private Video $video;

    public function getVideo(): Video
    {
        return $this->video;
    }


    #[ORM\Column(
        type: Types::STRING,
        nullable: false,
        enumType: AudioTranscriptionBcp47LanguageCode::class
    )]
    private AudioTranscriptionBcp47LanguageCode $originalLanguageBcp47LanguageCode;

    public function getOriginalLanguageBcp47LanguageCode(): AudioTranscriptionBcp47LanguageCode
    {
        return $this->originalLanguageBcp47LanguageCode;
    }

    public function setOriginalLanguageBcp47LanguageCode(
        AudioTranscriptionBcp47LanguageCode $originalLanguageBcp47LanguageCode
    ): void
    {
        $this->originalLanguageBcp47LanguageCode = $originalLanguageBcp47LanguageCode;
    }

    
    /** @var HappyScribeTranscription[]|Collection */
    #[ORM\OneToMany(
        mappedBy: 'audioTranscription',
        targetEntity: HappyScribeTranscription::class,
        cascade: ['persist']
    )]
    private array|Collection $happyScribeTranscriptions;

    /**
     * @return HappyScribeTranscription[]|Collection
     */
    public function getHappyScribeTranscriptions(): array|Collection
    {
        return $this->happyScribeTranscriptions;
    }

    public function addHappyScribeTranscription(
        HappyScribeTranscription $happyScribeTranscription
    ): void
    {
        foreach ($this->happyScribeTranscriptions as $existingHappyScribeTranscription) {
            if ($existingHappyScribeTranscription->getId() === $happyScribeTranscription->getId()) {
                throw new ValueError(
                    "Happy Scribe transcription '{$happyScribeTranscription->getId()}' already in list of Happy Scribe transcriptions of audio transcription '{$this->getId()}'."
                );
            }
        }

        $this->happyScribeTranscriptions->add($happyScribeTranscription);
    }
}
