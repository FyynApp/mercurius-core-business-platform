<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;


#[ORM\Entity]
#[ORM\Table(name: 'recordings_video_uploads')]
#[ORM\Index(
    fields: ['createdAt'],
    name: 'created_at_idx'
)]
class VideoUpload
{
    /**
     * @throws Exception
     */
    public function __construct(
        Video  $video,
        string $tusToken,
        string $fileName,
        string $fileType,
    )
    {
        $this->video = $video;
        $this->tusToken = $tusToken;
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
    private string $tusToken;

    public function getTusToken(): string
    {
        return $this->tusToken;
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
        mappedBy: 'videoUpload',
        targetEntity: Video::class,
        cascade: ['persist']
    )]
    private Video $video;

    public function getVideo(): Video
    {
        return $this->video;
    }
}
