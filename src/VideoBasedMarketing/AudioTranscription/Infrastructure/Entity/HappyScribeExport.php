<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Enum\HappyScribeExportFormat;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Enum\HappyScribeExportState;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;


#[ORM\Entity]
#[ORM\Table(name: 'audio_transcription_happy_scribe_exports')]
#[ORM\Index(
    fields: ['createdAt'],
    name: 'created_at_idx'
)]
class HappyScribeExport
{
    /**
     * @throws Exception
     */
    public function __construct(
        HappyScribeTranscription $happyScribeTranscription,
        string                   $happyScribeExportId,
        HappyScribeExportState   $happyScribeExportState,
        HappyScribeExportFormat $happyScribeExportFormat
    )
    {
        $this->happyScribeTranscription = $happyScribeTranscription;
        $this->happyScribeExportId = $happyScribeExportId;
        $this->state = $happyScribeExportState;
        $this->format = $happyScribeExportFormat;
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
        name: 'audio_exports_id',
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
        nullable: false,
        enumType: HappyScribeExportState::class
    )]
    private HappyScribeExportState $state;

    public function getState(): HappyScribeExportState
    {
        return $this->state;
    }

    public function setState(
        HappyScribeExportState $state
    ): void
    {
        $this->state = $state;
    }


    #[ORM\Column(
        type: Types::STRING,
        nullable: false,
        enumType: HappyScribeExportFormat::class
    )]
    private HappyScribeExportFormat $format;

    public function getFormat(): HappyScribeExportFormat
    {
        return $this->format;
    }


    #[ORM\Column(
        type: Types::STRING,
        nullable: false,
    )]
    private string $happyScribeExportId;

    public function getHappyScribeExportId(): string
    {
        return $this->happyScribeExportId;
    }


    #[ORM\Column(
        type: Types::STRING,
        length: 1024,
        nullable: true
    )]
    private ?string $downloadLink;

    public function getDownloadLink(): ?string
    {
        return $this->downloadLink;
    }

    public function setDownloadLink(string $downloadLink): void
    {
        $this->downloadLink = $downloadLink;
    }
}
