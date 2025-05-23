<?php

namespace App\VideoBasedMarketing\AudioTranscription\Domain\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\VideoBasedMarketing\LingoSync\Domain\Entity\LingoSyncProcess;
use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use App\VideoBasedMarketing\Organization\Domain\Entity\OrganizationOwnedEntityInterface;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;


#[ORM\Entity]
#[ORM\Table(name: 'audio_transcriptions')]
#[ORM\Index(
    fields: ['createdAt'],
    name: 'created_at_idx'
)]
class AudioTranscription
    implements OrganizationOwnedEntityInterface
{
    /**
     * @throws Exception
     */
    public function __construct(
        Video             $video,
        Bcp47LanguageCode $originalLanguageBcp47LanguageCode,
        ?LingoSyncProcess $lingoSyncProcess = null
    )
    {
        $this->video = $video;
        $this->originalLanguageBcp47LanguageCode = $originalLanguageBcp47LanguageCode;
        $this->lingoSyncProcess = $lingoSyncProcess;

        if (!is_null($lingoSyncProcess)) {
            $lingoSyncProcess->setAudioTranscription($this);
        }

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
        length: 16,
        nullable: false,
        enumType: Bcp47LanguageCode::class
    )]
    private Bcp47LanguageCode $originalLanguageBcp47LanguageCode;

    public function getOriginalLanguageBcp47LanguageCode(): Bcp47LanguageCode
    {
        return $this->originalLanguageBcp47LanguageCode;
    }

    public function getOrganization(): Organization
    {
        return $this->video->getOrganization();
    }


    #[ORM\OneToOne(
        inversedBy: 'audioTranscription',
        targetEntity: LingoSyncProcess::class,
        cascade: ['persist']
    )]
    #[ORM\JoinColumn(
        name: 'lingosync_processes_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?LingoSyncProcess $lingoSyncProcess;

    public function getLingoSyncProcess(): ?LingoSyncProcess
    {
        return $this->lingoSyncProcess;
    }

    public function setLingoSyncProcess(
        LingoSyncProcess $lingoSyncProcess
    ): void
    {
        $this->lingoSyncProcess = $lingoSyncProcess;
    }
}
