<?php

namespace App\VideoBasedMarketing\Mailings\Domain\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity]
#[ORM\Table(
    name: 'video_mailings',
    indexes: []
)]
#[ORM\Index(
    fields: ['createdAt'],
    name: 'created_at_idx'
)]
class VideoMailing
    implements UserOwnedEntityInterface
{
    public function __construct(
        User  $user,
        Video $video
    )
    {
        $this->user = $user;
        $this->video = $video;
        $this->createdAt = DateAndTimeService::getDateTimeUtc();
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


    #[ORM\ManyToOne(
        targetEntity: User::class,
        cascade: ['persist'],
        inversedBy: 'videoMailings'
    )]
    #[ORM\JoinColumn(
        name: 'users_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private User $user;

    public function getUser(): User
    {
        return $this->user;
    }


    #[ORM\ManyToOne(
        targetEntity: Video::class,
        cascade: ['persist'],
        inversedBy: 'videoMailings'
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
        type: 'datetime',
        nullable: false
    )]
    private DateTime $createdAt;

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }


    #[ORM\Column(
        type: 'string',
        length: 256,
        nullable: false
    )]
    #[Assert\Email]
    private string $receiverMailAddress = '';

    public function setReceiverMailAddress(string $receiverMailAddress): void
    {
        $this->receiverMailAddress = $receiverMailAddress;
    }

    public function getReceiverMailAddress(): string
    {
        return $this->receiverMailAddress;
    }


    #[ORM\Column(
        type: 'string',
        length: 512,
        nullable: false
    )]
    private string $subject = '';

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    
    #[ORM\Column(
        type: 'text',
        length: 4096,
        nullable: false
    )]
    private string $bodyAboveVideo = '';

    public function setBodyAboveVideo(string $bodyAboveVideo): void
    {
        $this->bodyAboveVideo = $bodyAboveVideo;
    }

    public function getBodyAboveVideo(): string
    {
        return $this->bodyAboveVideo;
    }

    
    #[ORM\Column(
        type: 'text',
        length: 4096,
        nullable: false
    )]
    private string $improvedBodyAboveVideo = '';

    public function setImprovedBodyAboveVideo(string $improvedBodyAboveVideo): void
    {
        $this->improvedBodyAboveVideo = $improvedBodyAboveVideo;
    }

    public function getImprovedBodyAboveVideo(): string
    {
        return $this->improvedBodyAboveVideo;
    }

    #[ORM\Column(
        type: 'boolean',
        nullable: false
    )]
    private bool $improvedBodyAboveVideoIsCurrentlyBeingGenerated = false;

    public function setImprovedBodyAboveVideoIsCurrentlyBeingGenerated(
        bool $improvedBodyAboveVideoIsCurrentlyBeingGenerated
    ): void
    {
        $this->improvedBodyAboveVideoIsCurrentlyBeingGenerated = $improvedBodyAboveVideoIsCurrentlyBeingGenerated;
    }

    public function isImprovedBodyAboveVideoCurrentlyBeingGenerated(): bool
    {
        return $this->improvedBodyAboveVideoIsCurrentlyBeingGenerated;
    }


    #[ORM\Column(
        type: 'text',
        length: 4096,
        nullable: false
    )]
    private string $bodyBelowVideo = '';

    public function setBodyBelowVideo(string $bodyBelowVideo): void
    {
        $this->bodyBelowVideo = $bodyBelowVideo;
    }

    public function getBodyBelowVideo(): string
    {
        return $this->bodyBelowVideo;
    }


    #[ORM\Column(
        type: 'text',
        length: 4096,
        nullable: false
    )]
    private string $improvedBodyBelowVideo = '';

    public function setImprovedBodyBelowVideo(string $improvedBodyBelowVideo): void
    {
        $this->improvedBodyBelowVideo = $improvedBodyBelowVideo;
    }

    public function getImprovedBodyBelowVideo(): string
    {
        return $this->improvedBodyBelowVideo;
    }

    #[ORM\Column(
        type: 'boolean',
        nullable: false
    )]
    private bool $improvedBodyBelowVideoIsCurrentlyBeingGenerated = false;

    public function setImprovedBodyBelowVideoIsCurrentlyBeingGenerated(
        bool $improvedBodyBelowVideoIsCurrentlyBeingGenerated
    ): void
    {
        $this->improvedBodyBelowVideoIsCurrentlyBeingGenerated = $improvedBodyBelowVideoIsCurrentlyBeingGenerated;
    }

    public function isImprovedBodyBelowVideoCurrentlyBeingGenerated(): bool
    {
        return $this->improvedBodyBelowVideoIsCurrentlyBeingGenerated;
    }
}
