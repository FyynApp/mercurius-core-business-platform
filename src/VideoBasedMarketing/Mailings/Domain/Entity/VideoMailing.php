<?php

namespace App\VideoBasedMarketing\Mailings\Domain\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;

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
        User $user
    )
    {
        $this->user = $user;
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
        inversedBy: 'recordingRequests'
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
        type: 'string',
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
        type: 'string',
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
}
