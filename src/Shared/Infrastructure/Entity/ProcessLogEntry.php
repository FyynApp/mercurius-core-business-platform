<?php

namespace App\Shared\Infrastructure\Entity;

use App\Shared\Infrastructure\Enum\ProcessLogEntryType;
use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Infrastructure\Entity\VideoUpload;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;


#[ORM\Entity]
#[ORM\Table(name: 'process_log_entries')]
#[ORM\Index(fields: ['startedAt'], name: 'started_at_idx')]
class ProcessLogEntry
{
    /**
     * @throws Exception
     */
    public function __construct(
        ProcessLogEntryType $processLogEntryType,
        ?User               $user = null,
        ?Organization       $organization = null,
        ?RecordingSession   $recordingSession = null,
        ?VideoUpload        $videoUpload = null,
        ?Video              $video = null,
    )
    {
        $this->type = $processLogEntryType;
        $this->startedAt = DateAndTimeService::getDateTime();

        $this->user = $user;
        $this->organization = $organization;
        $this->recordingSession = $recordingSession;
        $this->videoUpload = $videoUpload;
        $this->video = $video;

        if (is_null($user)) {
            if (!is_null($recordingSession)) {
                $this->user = $recordingSession->getUser();
            }
            if (!is_null($videoUpload)) {
                $this->user = $videoUpload->getVideo()->getUser();
            }
            if (!is_null($video)) {
                $this->user = $video->getUser();
            }
        }

        if (is_null($organization)) {
            if (!is_null($user)) {
                $this->organization = $user->getCurrentlyActiveOrganization();
            }
            if (!is_null($recordingSession)) {
                $this->organization = $recordingSession->getUser()->getCurrentlyActiveOrganization();
            }
            if (!is_null($videoUpload)) {
                $this->organization = $videoUpload->getVideo()->getOrganization();
            }
            if (!is_null($video)) {
                $this->organization = $video->getOrganization();
            }
        }
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
        nullable: false,
        enumType: ProcessLogEntryType::class
    )]
    private ProcessLogEntryType $type;

    public function getType(): ProcessLogEntryType
    {
        return $this->type;
    }


    #[ORM\ManyToOne(
        targetEntity: User::class,
        cascade: ['persist']
    )]
    #[ORM\JoinColumn(
        name: 'users_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?User $user;


    #[ORM\ManyToOne(
        targetEntity: Organization::class,
        cascade: ['persist']
    )]
    #[ORM\JoinColumn(
        name: 'organizations_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?Organization $organization;


    #[ORM\ManyToOne(
        targetEntity: RecordingSession::class,
        cascade: ['persist']
    )]
    #[ORM\JoinColumn(
        name: 'recording_sessions_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?RecordingSession $recordingSession;

    public function getRecordingSession(): ?RecordingSession
    {
        return $this->recordingSession;
    }


    #[ORM\ManyToOne(
        targetEntity: VideoUpload::class,
        cascade: ['persist']
    )]
    #[ORM\JoinColumn(
        name: 'recordings_video_uploads_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?VideoUpload $videoUpload;

    public function getVideoUpload(): ?VideoUpload
    {
        return $this->videoUpload;
    }


    #[ORM\ManyToOne(
        targetEntity: Video::class,
        cascade: ['persist']
    )]
    #[ORM\JoinColumn(
        name: 'videos_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?Video $video;

    public function getVideo(): ?Video
    {
        return $this->video;
    }


    #[ORM\Column(
        type: Types::DATETIME_MUTABLE,
        nullable: false
    )]
    private DateTime $startedAt;

    public function getStartedAt(): DateTime
    {
        return $this->startedAt;
    }


    #[ORM\Column(
        type: Types::DATETIME_MUTABLE,
        nullable: true
    )]
    private ?DateTime $finishedAt = null;

    public function getFinishedAt(): ?DateTime
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(DateTime $finishedAt): void
    {
        $this->finishedAt = $finishedAt;
    }


    #[ORM\Column(
        type: Types::STRING,
        length: 4096,
        unique: false,
        nullable: true
    )]
    private ?string $latestErrorMessage = null;

    public function getLatestErrorMessage(): ?string
    {
        return $this->latestErrorMessage;
    }

    public function setLatestErrorMessage(string $latestErrorMessage): void
    {
        $this->latestErrorMessage = mb_substr($latestErrorMessage, 0, 4096);
    }
}
