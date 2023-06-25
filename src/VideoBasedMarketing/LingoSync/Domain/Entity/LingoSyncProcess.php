<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\Entity;

use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\Shared\Domain\Enum\Gender;
use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscription;
use App\VideoBasedMarketing\LingoSync\Domain\Enum\LingoSyncProcessTaskStatus;
use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use App\VideoBasedMarketing\Organization\Domain\Entity\OrganizationOwnedEntityInterface;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;


#[ORM\Entity]
#[ORM\Table(name: 'lingosync_processes')]
#[ORM\Index(
    fields: ['createdAt'],
    name: 'created_at_idx'
)]
class LingoSyncProcess
    implements OrganizationOwnedEntityInterface, UserOwnedEntityInterface
{
    /**
     * @throws Exception
     */
    public function __construct(
        Video             $video,
        Bcp47LanguageCode $originalLanguage,
        Gender            $originalGender
    )
    {
        $this->video = $video;
        $this->originalLanguageBcp47LanguageCode = $originalLanguage;
        $this->originalGender = $originalGender;
        $this->createdAt = DateAndTimeService::getDateTime();
        $this->tasks = new ArrayCollection();
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


    #[ORM\ManyToOne(
        targetEntity: Video::class,
        cascade: ['persist'],
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
        type: Types::STRING,
        length: 16,
        nullable: false,
        enumType: Bcp47LanguageCode::class
    )]
    private Bcp47LanguageCode $originalLanguageBcp47LanguageCode;

    public function getOriginalLanguage(): Bcp47LanguageCode
    {
        return $this->originalLanguageBcp47LanguageCode;
    }

    public function getTargetLanguage(): ?Bcp47LanguageCode
    {
        foreach ($this->getTasks() as $task) {
            if (!is_null($task->getTargetLanguage())) {
                return $task->getTargetLanguage();
            }
        }
        return null;
    }

    #[ORM\Column(
        type: Types::STRING,
        length: 16,
        nullable: false,
        enumType: Gender::class
    )]
    private Gender $originalGender;

    public function getOriginalGender(): Gender
    {
        return $this->originalGender;
    }


    public function getOrganization(): Organization
    {
        return $this->video->getOrganization();
    }

    public function isFinished(): bool
    {
        /** @var LingoSyncProcessTask $task */
        foreach ($this->tasks as $task) {
            if (   $task->getStatus() === LingoSyncProcessTaskStatus::Initiated
                || $task->getStatus() === LingoSyncProcessTaskStatus::Running
            ) {
                return false;
            }
        }
        return true;
    }

    public function wasStopped(): bool
    {
        /** @var LingoSyncProcessTask $task */
        foreach ($this->tasks as $task) {
            if ($task->getStatus() === LingoSyncProcessTaskStatus::Stopped) {
                return true;
            }
        }
        return false;
    }

    public function hasErrored(): bool
    {
        /** @var LingoSyncProcessTask $task */
        foreach ($this->tasks as $task) {
            if ($task->getStatus() === LingoSyncProcessTaskStatus::Errored) {
                return true;
            }
        }
        return false;
    }

    /** @var LingoSyncProcessTask[] */
    #[ORM\OneToMany(
        mappedBy: 'lingoSyncProcess',
        targetEntity: LingoSyncProcessTask::class,
        cascade: ['persist'],
        orphanRemoval: true
    )]
    private array|Collection $tasks;

    public function addTask(LingoSyncProcessTask $task): void
    {
        $this->tasks[] = $task;
    }

    /** @return LingoSyncProcessTask[]|array|Collection */
    public function getTasks(): array|Collection
    {
        return $this->tasks;
    }


    #[ORM\OneToOne(
        mappedBy: 'lingoSyncProcess',
        targetEntity: AudioTranscription::class,
        cascade: ['persist']
    )]
    private ?AudioTranscription $audioTranscription = null;

    public function getAudioTranscription(): ?AudioTranscription
    {
        return $this->audioTranscription;
    }

    public function setAudioTranscription(
        ?AudioTranscription $audioTranscription
    ): void
    {
        $this->audioTranscription = $audioTranscription;
        $audioTranscription->setLingoSyncProcess($this);
    }


    /** @return Bcp47LanguageCode[] */
    public function getTargetLanguages(): array
    {
        $targetLanguages = [];

        foreach ($this->tasks as $task) {
            if (   !is_null($task->getTargetLanguage())
                && !in_array($task->getTargetLanguage(), $targetLanguages)
            ) {
                $targetLanguages[] = $task->getTargetLanguage();
            }
        }

        return $targetLanguages;
    }

    public function getUser(): User
    {
        return $this->video->getUser();
    }
}
