<?php

namespace App\Entity\Feature\Recordings;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Presentationpages\Presentationpage;
use App\Service\Aspect\DateAndTime\DateAndTimeService;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;

#[ORM\Entity]
#[ORM\Table(name: 'videos')]
#[ORM\Index(fields: ['createdAt'], name: "created_at_idx")]
class Video
{
    const ASSET_MIME_TYPE_WEBP = 'image/webp';
    const ASSET_MIME_TYPE_GIF = 'image/webp';
    const ASSET_MIME_TYPE_WEBM = 'video/webm';
    const ASSET_MIME_TYPE_MP4 = 'video/mp4';

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->createdAt = DateAndTimeService::getDateTimeUtc();
        $this->presentationpages = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(type: 'guid', unique: true)]
    private string $id;

    public function getId(): ?string
    {
        return $this->id;
    }


    #[ORM\Column(type: 'datetime', nullable: false)]
    private DateTime $createdAt;

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }


    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'], inversedBy: 'videos')]
    #[ORM\JoinColumn(name: 'users_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }


    #[ORM\OneToOne(inversedBy: 'video', targetEntity: RecordingSession::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'recording_sessions_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?RecordingSession $recordingSession;

    public function getRecordingSession(): ?RecordingSession
    {
        return $this->recordingSession;
    }

    public function setRecordingSession(?RecordingSession $recordingSession): void
    {
        if ($recordingSession->getUser()->getId() !== $this->getUser()->getId()) {
            throw new InvalidArgumentException("Trying to set recording session '{$recordingSession->getId()}' which belongs to user '{$recordingSession->getUser()->getId()}', while the video belongs to user '{$this->getUser()->getId()}'.");
        }
        $this->recordingSession = $recordingSession;
    }


    #[ORM\Column(type: 'boolean')]
    private bool $hasAssetPosterStillWebp = false;

    public function hasAssetPosterStillWebp(): bool
    {
        return $this->hasAssetPosterStillWebp;
    }

    public function setHasAssetPosterStillWebp(bool $hasAssetPosterStillWebp): void
    {
        $this->hasAssetPosterStillWebp = $hasAssetPosterStillWebp;
    }


    #[ORM\Column(type: 'boolean')]
    private bool $hasAssetPosterAnimatedWebp = false;

    public function hasAssetPosterAnimatedWebp(): bool
    {
        return $this->hasAssetPosterAnimatedWebp;
    }

    public function setHasAssetPosterAnimatedWebp(bool $hasAssetPosterAnimatedWebp): void
    {
        $this->hasAssetPosterAnimatedWebp = $hasAssetPosterAnimatedWebp;
    }


    #[ORM\Column(type: 'boolean')]
    private bool $hasAssetPosterAnimatedGif = false;

    public function hasAssetPosterAnimatedGif(): bool
    {
        return $this->hasAssetPosterAnimatedGif;
    }

    public function setHasAssetPosterAnimatedGif(bool $hasAssetPosterAnimatedGif): void
    {
        $this->hasAssetPosterAnimatedGif = $hasAssetPosterAnimatedGif;
    }


    #[ORM\Column(type: 'boolean')]
    private bool $hasAssetFullWebm = false;

    public function hasAssetFullWebm(): bool
    {
        return $this->hasAssetFullWebm;
    }

    public function setHasAssetFullWebm(bool $hasAssetFullWebm): void
    {
        $this->hasAssetFullWebm = $hasAssetFullWebm;
    }


    #[ORM\Column(type: 'boolean')]
    private bool $hasAssetFullMp4 = false;

    public function hasAssetFullMp4(): bool
    {
        return $this->hasAssetFullMp4;
    }

    public function setHasAssetFullMp4(bool $hasAssetFullMp4): void
    {
        $this->hasAssetFullMp4 = $hasAssetFullMp4;
    }


    /** @var Presentationpage[]|Collection */
    #[ORM\OneToMany(mappedBy: 'video', targetEntity: Presentationpage::class, cascade: ['persist'])]
    private Collection $presentationpages;

    /**
     * @return Presentationpage[]|Collection
     */
    public function getPresentationpages(): Collection
    {
        return $this->presentationpages;
    }
}
