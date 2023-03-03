<?php

namespace App\VideoBasedMarketing\RecordingRequests\Domain\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use App\VideoBasedMarketing\Organization\Domain\Entity\OrganizationOwnedEntityInterface;
use App\VideoBasedMarketing\RecordingRequests\Domain\Enum\RecordingRequestResponseStatus;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;


#[ORM\Entity]
#[ORM\Table(name: 'recording_request_responses')]
#[ORM\Index(
    fields: ['createdAt'],
    name: 'created_at_idx'
)]
class RecordingRequestResponse
    implements UserOwnedEntityInterface, OrganizationOwnedEntityInterface
{
    /**
     * @throws Exception
     */
    public function __construct(
        User             $user,
        RecordingRequest $recordingRequest
    )
    {
        $this->user = $user;
        $this->organization = $user->getCurrentlyActiveOrganization();
        $this->recordingRequest = $recordingRequest;
        $this->videos = new ArrayCollection();
        $this->createdAt = DateAndTimeService::getDateTime();
        $this->status = RecordingRequestResponseStatus::UNANSWERED;
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


    #[ORM\ManyToOne(
        targetEntity: User::class,
        cascade: ['persist'],
        inversedBy: 'recordingRequestResponses'
    )]
    #[ORM\JoinColumn(
        name: 'users_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?User $user;

    public function getUser(): User
    {
        if (is_null($this->user)) {
            return $this->organization->getOwningUser();
        }
        return $this->user;
    }


    #[ORM\ManyToOne(
        targetEntity: Organization::class,
        cascade: ['persist']
    )]
    #[ORM\JoinColumn(
        name: 'organizations_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private Organization $organization;

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function setOrganization(
        Organization $organization
    ): void
    {
        $this->organization = $organization;
    }


    #[ORM\ManyToOne(
        targetEntity: RecordingRequest::class,
        cascade: ['persist'],
        inversedBy: 'recordingRequestResponses'
    )]
    #[ORM\JoinColumn(
        name: 'recording_requests_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private RecordingRequest $recordingRequest;

    public function getRecordingRequest(): RecordingRequest
    {
        return $this->recordingRequest;
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
        type: Types::STRING,
        nullable: false,
        enumType: RecordingRequestResponseStatus::class
    )]
    private RecordingRequestResponseStatus $status;

    public function getStatus(): RecordingRequestResponseStatus
    {
        return $this->status;
    }

    public function setStatus(
        RecordingRequestResponseStatus $status
    ): void
    {
        $this->status = $status;
    }


    #[ORM\OneToMany(
        mappedBy: 'recordingRequestResponse',
        targetEntity: Video::class,
        cascade: ['persist']
    )]
    private array|Collection $videos;

    public function getVideos(): array|Collection
    {
        return $this->videos;
    }

    public function addVideo(
        Video $video
    ): void
    {
        $this->videos->add($video);
    }
}
