<?php

namespace App\VideoBasedMarketing\RecordingRequests\Domain\Entity;

use App\Shared\Infrastructure\Entity\SupportsShortIdInterface;
use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
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
#[ORM\Table(name: 'recording_requests')]
#[ORM\Index(
    fields: ['createdAt'],
    name: 'created_at_idx'
)]
class RecordingRequest
    implements UserOwnedEntityInterface, OrganizationOwnedEntityInterface, SupportsShortIdInterface
{
    /**
     * @throws Exception
     */
    public function __construct(
        User   $user,
        string $requestText,
        ?Video $requestVideo = null
    )
    {
        $this->user = $user;
        $this->requestText = $requestText;
        $this->requestVideo = $requestVideo;
        $this->organization = $user->getCurrentlyActiveOrganization();
        $this->recordingRequestResponses = new ArrayCollection();
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


    #[ORM\ManyToOne(
        targetEntity: User::class,
        cascade: ['persist'],
        inversedBy: 'recordingRequests'
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
        length: 256,
        nullable: true
    )]
    private ?string $title = null;

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }


    #[ORM\ManyToOne(
        targetEntity: Video::class,
        cascade: ['persist']
    )]
    #[ORM\JoinColumn(
        name: 'request_videos_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?Video $requestVideo;

    public function getRequestVideo(): ?Video
    {
        return $this->requestVideo;
    }


    #[ORM\Column(type: Types::TEXT)]
    private string $requestText;

    public function getRequestText(): string
    {
        return $this->requestText;
    }


    #[ORM\OneToMany(
        mappedBy: 'recordingRequest',
        targetEntity: RecordingRequestResponse::class,
        cascade: ['persist']
    )]
    private array|Collection $recordingRequestResponses;

    /** @return RecordingRequestResponse[]|Collection */
    public function getRecordingRequestResponses(): array|Collection
    {
        return $this->recordingRequestResponses;
    }

    public function addResponse(
        RecordingRequestResponse $response
    ): void
    {
        $this->recordingRequestResponses->add($response);
    }
}
