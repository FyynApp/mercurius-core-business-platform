<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use ValueError;


#[ORM\Entity]
#[ORM\Table(name: 'video_player_session_events')]
class VideoPlayerSessionEvent
{
    /**
     * @throws Exception
     */
    public function __construct(
        VideoPlayerSession $videoPlayerSession,
        float              $playerCurrentTime
    )
    {
        $this->videoPlayerSession = $videoPlayerSession;
        $this->createdAt = DateAndTimeService::getDateTime();

        if ($playerCurrentTime < 0) {
            throw new ValueError("currentTime must be > 0 but is '$playerCurrentTime'.");
        }
        $this->playerCurrentTime = $playerCurrentTime;
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


    #[ORM\Column(
        type: Types::FLOAT,
        nullable: false,
        options: ['unsigned' => true]
    )]
    private float $playerCurrentTime;


    #[ORM\ManyToOne(
        targetEntity: VideoPlayerSession::class,
        cascade: ['persist']
    )]
    #[ORM\JoinColumn(
        name: 'video_player_sessions_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private VideoPlayerSession $videoPlayerSession;

    public function getVideoPlayerSession(): VideoPlayerSession
    {
        return $this->videoPlayerSession;
    }
}
