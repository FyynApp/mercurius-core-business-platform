<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Entity;

use App\Shared\Infrastructure\Entity\SupportsShortIdInterface;
use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
use App\VideoBasedMarketing\Mailings\Domain\Entity\VideoMailing;
use App\VideoBasedMarketing\Presentationpages\Domain\Entity\Presentationpage;
use App\VideoBasedMarketing\RecordingRequests\Domain\Entity\RecordingRequestResponse;
use App\VideoBasedMarketing\Recordings\Infrastructure\Entity\VideoUpload;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use InvalidArgumentException;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;


#[ORM\Entity]
#[ORM\Table(name: 'videos')]
#[ORM\Index(
    fields: ['createdAt'],
    name: 'created_at_idx'
)]
class Video
    implements UserOwnedEntityInterface, SupportsShortIdInterface
{
    /**
     * @throws Exception
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->presentationpages = new ArrayCollection();
        $this->videoMailings = new ArrayCollection();
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
        type: 'string',
        length: 12,
        unique: true,
        nullable: true
    )]
    private ?string $shortId = null;

    public function setShortId(string $shortId): void
    {
        $this->shortId = $shortId;
    }

    public function getShortId(): ?string
    {
        return $this->shortId;
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


    #[ORM\ManyToOne(
        targetEntity: User::class,
        cascade: ['persist'],
        inversedBy: 'videos'
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

    public function setUser(User $user): void
    {
        $this->user = $user;
    }


    #[ORM\Column(type: 'boolean')]
    private bool $isDeleted = false;

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): void
    {
        $this->isDeleted = $isDeleted;
    }


    #[ORM\Column(
        type: 'string',
        length: 512,
        nullable: false
    )]
    private string $title = '';

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }


    #[ORM\OneToOne(
        inversedBy: 'video',
        targetEntity: RecordingSession::class,
        cascade: ['persist']
    )]
    #[ORM\JoinColumn(
        name: 'recording_sessions_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?RecordingSession $recordingSession = null;

    public function getRecordingSession(): ?RecordingSession
    {
        return $this->recordingSession;
    }

    public function setRecordingSession(?RecordingSession $recordingSession): void
    {
        if ($recordingSession
                ->getUser()
                ->getId()
            !==
            $this
                ->getUser()
                ->getId()
        ) {
            throw new InvalidArgumentException(
                "Trying to set recording session '{$recordingSession->getId()}' which belongs to user '{$recordingSession->getUser()->getId()}', while the video belongs to user '{$this->getUser()->getId()}'."
            );
        }
        $this->recordingSession = $recordingSession;
    }


    #[ORM\OneToOne(
        inversedBy: 'video',
        targetEntity: VideoUpload::class,
        cascade: ['persist']
    )]
    #[ORM\JoinColumn(
        name: 'video_uploads_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?VideoUpload $videoUpload;

    public function getVideoUpload(): ?VideoUpload
    {
        return $this->videoUpload;
    }

    public function setVideoUpload(?VideoUpload $videoUpload): void
    {
        $this->videoUpload = $videoUpload;
    }


    #[ORM\ManyToOne(
        targetEntity: RecordingRequestResponse::class,
        cascade: ['persist'],
        inversedBy: 'videos'
    )]
    #[ORM\JoinColumn(
        name: 'recording_request_responses_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?RecordingRequestResponse $recordingRequestResponse;

    public function getRecordingRequestResponse(): ?RecordingRequestResponse
    {
        return $this->recordingRequestResponse;
    }

    public function setRecordingRequestResponse(
        ?RecordingRequestResponse $recordingRequestResponse
    ): void
    {
        if ($recordingRequestResponse
                ->getUser()
                ->getId()
            !==
            $this
                ->getUser()
                ->getId()
        ) {
            throw new InvalidArgumentException(
                "Trying to set recording request response '{$recordingRequestResponse->getId()}' which belongs to user '{$recordingRequestResponse->getUser()->getId()}', while the video belongs to user '{$this->getUser()->getId()}'."
            );
        }
        $this->recordingRequestResponse = $recordingRequestResponse;
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


    #[ORM\Column(
        type: 'smallint',
        nullable: true,
        options: ['unsigned' => true]
    )]
    private ?float $assetPosterStillWebpWidth = null;

    public function getAssetPosterStillWebpWidth(): ?int
    {
        return $this->assetPosterStillWebpWidth;
    }

    public function setAssetPosterStillWebpWidth(?int $val): void
    {
        $this->assetPosterStillWebpWidth = $val;
    }


    #[ORM\Column(
        type: 'smallint',
        nullable: true,
        options: ['unsigned' => true]
    )]
    private ?float $assetPosterStillWebpHeight = null;

    public function getAssetPosterStillWebpHeight(): ?int
    {
        return $this->assetPosterStillWebpHeight;
    }

    public function setAssetPosterStillWebpHeight(?int $val): void
    {
        $this->assetPosterStillWebpHeight = $val;
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


    #[ORM\Column(
        type: 'smallint',
        nullable: true,
        options: ['unsigned' => true]
    )]
    private ?float $assetPosterAnimatedWebpWidth = null;

    public function getAssetPosterAnimatedWebpWidth(): ?int
    {
        return $this->assetPosterAnimatedWebpWidth;
    }

    public function setAssetPosterAnimatedWebpWidth(?int $val): void
    {
        $this->assetPosterAnimatedWebpWidth = $val;
    }


    #[ORM\Column(
        type: 'smallint',
        nullable: true,
        options: ['unsigned' => true]
    )]
    private ?float $assetPosterAnimatedWebpHeight = null;

    public function getAssetPosterAnimatedWebpHeight(): ?int
    {
        return $this->assetPosterAnimatedWebpHeight;
    }

    public function setAssetPosterAnimatedWebpHeight(?int $val): void
    {
        $this->assetPosterAnimatedWebpHeight = $val;
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


    #[ORM\Column(
        type: 'float',
        nullable: true,
        options: ['unsigned' => true]
    )]
    private ?float $assetFullWebmFps = null;

    public function getAssetFullWebmFps(): ?float
    {
        return $this->assetFullWebmFps;
    }

    public function setAssetFullWebmFps(?float $val): void
    {
        $this->assetFullWebmFps = $val;
    }


    #[ORM\Column(
        type: 'float',
        nullable: true,
        options: ['unsigned' => true]
    )]
    private ?float $assetFullWebmSeconds = null;

    public function getAssetFullWebmSeconds(): ?float
    {
        return $this->assetFullWebmSeconds;
    }

    public function setAssetFullWebmSeconds(?float $val): void
    {
        $this->assetFullWebmSeconds = $val;
    }


    #[ORM\Column(
        type: 'smallint',
        nullable: true,
        options: ['unsigned' => true]
    )]
    private ?float $assetFullWebmWidth = null;

    public function getAssetFullWebmWidth(): ?int
    {
        return $this->assetFullWebmWidth;
    }

    public function setAssetFullWebmWidth(?int $val): void
    {
        $this->assetFullWebmWidth = $val;
    }


    #[ORM\Column(
        type: 'smallint',
        nullable: true,
        options: ['unsigned' => true]
    )]
    private ?float $assetFullWebmHeight = null;

    public function getAssetFullWebmHeight(): ?int
    {
        return $this->assetFullWebmHeight;
    }

    public function setAssetFullWebmHeight(?int $val): void
    {
        $this->assetFullWebmHeight = $val;
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


    #[ORM\Column(
        type: 'float',
        nullable: true,
        options: ['unsigned' => true]
    )]
    private ?float $assetFullMp4Fps = null;

    public function getAssetFullMp4Fps(): ?float
    {
        return $this->assetFullMp4Fps;
    }

    public function setAssetFullMp4Fps(?float $val): void
    {
        $this->assetFullMp4Fps = $val;
    }


    #[ORM\Column(
        type: 'float',
        nullable: true,
        options: ['unsigned' => true]
    )]
    private ?float $assetFullMp4Seconds = null;

    public function getAssetFullMp4Seconds(): ?float
    {
        return $this->assetFullMp4Seconds;
    }

    public function setAssetFullMp4Seconds(?float $val): void
    {
        $this->assetFullMp4Seconds = $val;
    }


    #[ORM\Column(
        type: 'smallint',
        nullable: true,
        options: ['unsigned' => true]
    )]
    private ?float $assetFullMp4Width = null;

    public function getAssetFullMp4Width(): ?int
    {
        return $this->assetFullMp4Width;
    }

    public function setAssetFullMp4Width(?int $val): void
    {
        $this->assetFullMp4Width = $val;
    }


    #[ORM\Column(
        type: 'smallint',
        nullable: true,
        options: ['unsigned' => true]
    )]
    private ?float $assetFullMp4Height = null;

    public function getAssetFullMp4Height(): ?int
    {
        return $this->assetFullMp4Height;
    }

    public function setAssetFullMp4Height(?int $val): void
    {
        $this->assetFullMp4Height = $val;
    }

    
    #[ORM\Column(type: 'boolean')]
    private bool $hasAssetPosterStillWithPlayOverlayForEmailPng = false;

    public function hasAssetPosterStillWithPlayOverlayForEmailPng(): bool
    {
        return $this->hasAssetPosterStillWithPlayOverlayForEmailPng;
    }

    public function setHasAssetPosterStillWithPlayOverlayForEmailPng(
        bool $hasAssetPosterStillWithPlayOverlayForEmailPng
    ): void
    {
        $this->hasAssetPosterStillWithPlayOverlayForEmailPng = $hasAssetPosterStillWithPlayOverlayForEmailPng;
    }

    
    #[ORM\Column(
        type: 'smallint',
        nullable: true,
        options: ['unsigned' => true]
    )]
    private ?float $assetPosterStillWithPlayOverlayForEmailPngWidth = null;

    public function getAssetPosterStillWithPlayOverlayForEmailPngWidth(): ?int
    {
        return $this->assetPosterStillWithPlayOverlayForEmailPngWidth;
    }

    public function setAssetPosterStillWithPlayOverlayForEmailPngWidth(?int $val): void
    {
        $this->assetPosterStillWithPlayOverlayForEmailPngWidth = $val;
    }


    #[ORM\Column(
        type: 'smallint',
        nullable: true,
        options: ['unsigned' => true]
    )]
    private ?float $assetPosterStillWithPlayOverlayForEmailPngHeight = null;

    public function getAssetPosterStillWithPlayOverlayForEmailPngHeight(): ?int
    {
        return $this->assetPosterStillWithPlayOverlayForEmailPngHeight;
    }

    public function setAssetPosterStillWithPlayOverlayForEmailPngHeight(?int $val): void
    {
        $this->assetPosterStillWithPlayOverlayForEmailPngHeight = $val;
    }


    /** @var Presentationpage[]|Collection */
    #[ORM\OneToMany(
        mappedBy: 'video',
        targetEntity: Presentationpage::class,
        cascade: ['persist']
    )]
    private array|Collection $presentationpages;

    /**
     * @return Presentationpage[]|Collection
     */
    public function getPresentationpages(): array|Collection
    {
        return $this->presentationpages;
    }

    #[ORM\ManyToOne(
        targetEntity: Presentationpage::class,
        cascade: ['persist']
    )]
    #[ORM\JoinColumn(
        name: 'video_only_presentationpage_template_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?Presentationpage $videoOnlyPresentationpageTemplate = null;

    public function getVideoOnlyPresentationpageTemplate(): ?Presentationpage
    {
        return $this->videoOnlyPresentationpageTemplate;
    }

    public function setVideoOnlyPresentationpageTemplate(?Presentationpage $videoOnlyPresentationpageTemplate): void
    {
        $this->videoOnlyPresentationpageTemplate = $videoOnlyPresentationpageTemplate;
    }


    #[ORM\OneToMany(
        mappedBy: 'video',
        targetEntity: VideoMailing::class,
        cascade: ['persist']
    )]
    private array|Collection $videoMailings;

    /** @return VideoMailing[]|Collection */
    public function getVideoMailings(): array|Collection
    {
        return $this->videoMailings;
    }

    public function addVideoMailing(
        VideoMailing $videoMailing
    ): void
    {
        if (!$this->videoMailings->contains($videoMailing)) {
            $this->videoMailings->add($videoMailing);
        }
    }


    public function isFullAssetAvailable(): bool
    {
        return $this->hasAssetFullMp4 || $this->hasAssetFullWebm();
    }

    public function getDuration(): ?string
    {
        $seconds = null;
        if (!is_null($this->getAssetFullMp4Seconds())) {
            $seconds = $this->getAssetFullMp4Seconds();
        } elseif (!is_null($this->getAssetFullWebmSeconds())) {
            $seconds = $this->getAssetFullWebmSeconds();
        }

        if (is_null($seconds)) {
            return null;
        } else {
            return gmdate('i:s', (int)$seconds);
        }
    }
}
