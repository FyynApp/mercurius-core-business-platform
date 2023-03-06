<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;


#[ORM\Entity]
#[ORM\Table(name: 'video_player_sessions')]
class VideoPlayerSession
{
    /**
     * @throws Exception
     */
    public function __construct(
        Video  $video,
        string $ipAddress
    )
    {
        $this->video = $video;
        $this->ipAddress = $ipAddress;
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
        type: Types::STRING,
        length: 256,
        unique: false,
        nullable: false
    )]
    private string $ipAddress;

    public function getIpAddress(): string
    {
        return $this->ipAddress;
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
        cascade: ['persist']
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
}
