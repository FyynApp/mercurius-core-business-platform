<?php

namespace App\VideoBasedMarketing\AudioTranscription\Domain\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\AudioTranscription\Domain\Enum\AudioTranscriptionBcp47LanguageCode;
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
        Video                               $video,
        AudioTranscriptionBcp47LanguageCode $originalLanguageBcp47LanguageCode
    )
    {
        $this->video = $video;
        $this->originalLanguageBcp47LanguageCode = $originalLanguageBcp47LanguageCode;
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
        enumType: AudioTranscriptionBcp47LanguageCode::class
    )]
    private AudioTranscriptionBcp47LanguageCode $originalLanguageBcp47LanguageCode;

    public function getOriginalLanguageBcp47LanguageCode(): AudioTranscriptionBcp47LanguageCode
    {
        return $this->originalLanguageBcp47LanguageCode;
    }

    public function getOrganization(): Organization
    {
        return $this->video->getOrganization();
    }
}
