<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;


#[ORM\Entity]
#[ORM\Table(name: 'tus_uploads')]
#[ORM\Index(
    fields: ['createdAt'],
    name: 'created_at_idx'
)]
class TusUpload
{
    /**
     * @throws Exception
     */
    public function __construct(
        Video  $video,
        string $token,
        string $fileName,
        string $fileType,
    )
    {
        $this->video = $video;
        $this->token = $token;
        $this->fileName = $fileName;
        $this->fileType = $fileType;
        $this->createdAt = DateAndTimeService::getDateTime();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(
        type: 'guid',
        unique: true
    )]
    private ?string $id = null;

    public function getId(): ?string
    {
        return $this->id;
    }


    #[ORM\Column(
        type: 'guid',
        unique: true
    )]
    private string $token;

    public function getToken(): string
    {
        return $this->token;
    }


    #[ORM\Column(
        type: 'string',
        length: 256,
        unique: false,
        nullable: false
    )]
    private string $fileName;

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }


    #[ORM\Column(
        type: 'string',
        length: 32,
        unique: false,
        nullable: false
    )]
    private string $fileType;

    public function getFileType(): string
    {
        return $this->fileType;
    }

    #[ORM\Column(
        type: 'datetime',
        nullable: false
    )]
    private DateTime $createdAt;

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }


    #[ORM\OneToOne(
        mappedBy: 'tusUpload',
        targetEntity: Video::class,
        cascade: ['persist']
    )]
    private Video $video;

    public function getVideo(): Video
    {
        return $this->video;
    }
}
