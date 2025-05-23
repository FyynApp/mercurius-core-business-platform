<?php

namespace App\VideoBasedMarketing\AudioTranscription\Domain\Entity;

use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use App\VideoBasedMarketing\Organization\Domain\Entity\OrganizationOwnedEntityInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;


#[ORM\Entity]
#[ORM\Table(name: 'audio_transcription_web_vtts')]
#[ORM\Index(
    fields: ['audioTranscription', 'bcp47LanguageCode'],
    name: 'main_idx'
)]
#[ORM\Index(
    fields: ['vttContent'],
    name: 'vtt_content_fulltext_idx',
    flags: ['fulltext']
)]
class AudioTranscriptionWebVtt
    implements OrganizationOwnedEntityInterface
{
    /**
     * @throws Exception
     */
    public function __construct(
        AudioTranscription $audioTranscription,
        Bcp47LanguageCode  $bcp47LanguageCode,
        string             $vttContent
    )
    {
        $this->audioTranscription = $audioTranscription;
        $this->bcp47LanguageCode = $bcp47LanguageCode;
        $this->vttContent = $vttContent;
    }

    public function getOrganization(): Organization
    {
        return $this->audioTranscription->getOrganization();
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
        enumType: Bcp47LanguageCode::class
    )]
    private Bcp47LanguageCode $bcp47LanguageCode;

    public function getBcp47LanguageCode(): Bcp47LanguageCode
    {
        return $this->bcp47LanguageCode;
    }

    public function setBcp47LanguageCode(
        Bcp47LanguageCode $bcp47LanguageCode
    ): void
    {
        $this->bcp47LanguageCode = $bcp47LanguageCode;
    }


    public function isOriginalLanguage(): bool
    {
        return $this->bcp47LanguageCode
            === $this->audioTranscription->getOriginalLanguageBcp47LanguageCode();
    }


    #[ORM\Column(
        type: Types::TEXT,
        nullable: false
    )]
    private string $vttContent;

    public function getVttContent(): string
    {
        return preg_replace('/\r\n?/', "\n", $this->vttContent);
    }

    public function setVttContent(
        string $vttContent
    ): void
    {
        $this->vttContent = preg_replace('/\r\n?/', "\n", $vttContent);
    }
}
