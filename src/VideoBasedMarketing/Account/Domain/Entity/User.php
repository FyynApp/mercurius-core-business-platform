<?php

namespace App\VideoBasedMarketing\Account\Domain\Entity;

use App\Shared\Domain\Enum\Iso639_1Code;
use App\VideoBasedMarketing\Account\Domain\Enum\Role;
use App\VideoBasedMarketing\Account\Domain\Enum\VideosListViewMode;
use App\VideoBasedMarketing\Account\Infrastructure\Entity\ActiveCampaignContact;
use App\VideoBasedMarketing\Account\Infrastructure\Entity\ThirdPartyAuthLinkedinResourceOwner;
use App\VideoBasedMarketing\Account\Infrastructure\Repository\UserRepository;
use App\VideoBasedMarketing\Mailings\Domain\Entity\VideoMailing;
use App\VideoBasedMarketing\Membership\Domain\Entity\Subscription;
use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use App\VideoBasedMarketing\Presentationpages\Domain\Entity\Presentationpage;
use App\VideoBasedMarketing\RecordingRequests\Domain\Entity\RecordingRequest;
use App\VideoBasedMarketing\RecordingRequests\Domain\Entity\RecordingRequestResponse;
use App\VideoBasedMarketing\Recordings\Api\Recorder\V1\Entity\RecordingSettingsBag;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use ValueError;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[UniqueEntity(
    fields: ['email'],
    message: 'There is already an account with this email'
)]
class User
    implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct()
    {
        $this->presentationpages = new ArrayCollection();
        $this->recordingSessions = new ArrayCollection();
        $this->recordingSettingsBags = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
        $this->videos = new ArrayCollection();
        $this->recordingRequests = new ArrayCollection();
        $this->recordingRequestResponses = new ArrayCollection();
        $this->videoMailings = new ArrayCollection();
        $this->ownedOrganizations = new ArrayCollection();
        $this->joinedOrganizations = new ArrayCollection();
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


    /** @var Organization[] */
    #[ORM\OneToMany(
        mappedBy: 'owningUser',
        targetEntity: Organization::class,
        cascade: ['persist']
    )]
    private array|Collection $ownedOrganizations;

    public function getOwnedOrganizations(): array|Collection
    {
        return $this->ownedOrganizations;
    }

    /**
     * @throws Exception
     */
    public function addOwnedOrganization(
        Organization $organization
    ): void
    {
        foreach ($this->ownedOrganizations as $ownedOrganization) {
            if ($ownedOrganization->getId() === $organization->getId()) {
                throw new ValueError(
                    "Organization '{$organization->getId()}' already in list of owned organizations."
                );
            }
        }
        $this->ownedOrganizations->add($organization);
    }


    #[ORM\ManyToOne(
        targetEntity: Organization::class,
        cascade: ['persist']
    )]
    #[ORM\JoinColumn(
        name: 'currently_active_organizations_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'CASCADE'
    )]
    private ?Organization $currentlyActiveOrganization = null;

    public function getCurrentlyActiveOrganization(): Organization
    {
        return $this->currentlyActiveOrganization;
    }

    public function setCurrentlyActiveOrganization(
        Organization $organization
    ): void
    {
        foreach ($this->ownedOrganizations as $ownedOrganization) {
            if ($ownedOrganization->getId() === $organization->getId()) {
                $this->currentlyActiveOrganization = $organization;
                return;
            }
        }

        foreach ($this->joinedOrganizations as $joinedOrganization) {
            if ($joinedOrganization->getId() === $organization->getId()) {
                $this->currentlyActiveOrganization = $organization;
                return;
            }
        }

        throw new ValueError(
            "Cannot set organization '{$organization->getId()}' as currently active because it is neither owned nor joined."
        );
    }


    /**
     * @var Collection|Organization[]
     */
    #[ORM\JoinTable(name: 'users_organizations')]
    #[ORM\JoinColumn(
        name: 'users_id',
        referencedColumnName: 'id',
        unique: false
    )]
    #[ORM\InverseJoinColumn(
        name: 'organizations_id',
        referencedColumnName: 'id',
        unique: false
    )]
    #[ORM\ManyToMany(targetEntity: Organization::class, inversedBy: 'joinedUsers')]
    private array|Collection $joinedOrganizations;

    /**
     * @return Collection|Organization[]
     */
    public function getJoinedOrganizations(): Collection|array
    {
        return $this->joinedOrganizations;
    }

    public function addJoinedOrganization(
        Organization $organization
    ): void
    {
        foreach ($this->joinedOrganizations as $joinedOrganization) {
            if ($joinedOrganization->getId() === $organization->getId()) {
                throw new ValueError(
                    "Organization '{$organization->getId()}' already in list of joined organizations."
                );
            }
        }

        $this->joinedOrganizations->add($organization);
    }

    #[ORM\Column(
        type: Types::STRING,
        length: 180,
        unique: true,
        nullable: false
    )]
    private ?string $email = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = trim(mb_strtolower($email));
    }


    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = Role::USER->value;

        return array_unique($roles);
    }

    public function hasRole(Role $role): bool
    {
        return in_array(
            strtoupper($role->value),
            $this->getRoles(),
            true
        );
    }

    public function addRole(Role $role): void
    {
        $role = $role->value;
        $role = strtoupper($role);

        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }
    }

    public function removeRole(Role $roleToRemove): void
    {
        $remainingRoles = [];
        foreach ($this->roles as $role) {
            if ($role !== $roleToRemove->value) {
                $remainingRoles[] = $role;
            }
        }
        $this->roles = $remainingRoles;
    }


    #[ORM\Column(type: Types::STRING)]
    private string $password;

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }


    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isVerified = false;

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): void
    {
        $this->isVerified = $isVerified;
    }


    public function isRegistered(): bool
    {
        return $this->hasRole(Role::REGISTERED_USER);
    }

    public function isUnregistered(): bool
    {
        return $this->hasRole(Role::UNREGISTERED_USER);
    }

    public function isExtensionOnly(): bool
    {
        return $this->hasRole(Role::EXTENSION_ONLY_USER);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN);
    }


    #[ORM\OneToOne(
        mappedBy: 'user',
        targetEntity: ThirdPartyAuthLinkedinResourceOwner::class,
        cascade: ['persist']
    )]
    private ?ThirdPartyAuthLinkedinResourceOwner $thirdPartyAuthLinkedinResourceOwner = null;

    public function getThirdPartyAuthLinkedinResourceOwner(): ?ThirdPartyAuthLinkedinResourceOwner
    {
        return $this->thirdPartyAuthLinkedinResourceOwner;
    }

    #[ORM\Column(
        type: Types::STRING,
        length: 2,
        nullable: true,
        enumType: Iso639_1Code::class
    )]
    private ?Iso639_1Code $uiLanguageCode;

    public function getUiLanguageCode(): ?Iso639_1Code
    {
        return $this->uiLanguageCode;
    }

    public function setUiLanguageCode(
        ?Iso639_1Code $iso639_1Code
    ): void
    {
        $this->uiLanguageCode = $iso639_1Code;
    }


    #[ORM\Column(
        type: Types::STRING,
        length: 128,
        nullable: true
    )]
    private ?string $uiTimezone;

    public function getUiTimezone(): ?string
    {
        return $this->uiTimezone;
    }

    public function setUiTimezone(
        ?string $uiTimezone
    ): void
    {
        $this->uiTimezone = $uiTimezone;
    }


    #[ORM\OneToOne(
        mappedBy: 'user',
        targetEntity: ActiveCampaignContact::class,
        cascade: ['persist']
    )]
    private ?ActiveCampaignContact $activeCampaignContact = null;

    public function getActiveCampaignContact(): ?ActiveCampaignContact
    {
        return $this->activeCampaignContact;
    }

    public function setActiveCampaignContact(
        ?ActiveCampaignContact $activeCampaignContact
    ): void
    {
        $this->activeCampaignContact = $activeCampaignContact;
    }


    /** @var Subscription[]|Collection */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: Subscription::class,
        cascade: ['persist']
    )]
    private array|Collection $subscriptions;

    /**
     * @return Subscription[]|Collection
     */
    public function getSubscriptions(): array|Collection
    {
        return $this->subscriptions;
    }


    /** @var Presentationpage[]|Collection */
    #[ORM\OneToMany(
        mappedBy: 'user',
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


    /** @var RecordingSession[]|Collection */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: RecordingSession::class,
        cascade: ['persist']
    )]
    private array|Collection $recordingSessions;

    /**
     * @return RecordingSession[]|Collection
     */
    public function getRecordingSessions(): array|Collection
    {
        return $this->recordingSessions;
    }

    public function setRecordingSessions(array|Collection $recordingSessions): void
    {
        $this->recordingSessions = $recordingSessions;
    }


    /** @var Video[]|Collection */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: Video::class,
        cascade: ['persist']
    )]
    private array|Collection $videos;

    /**
     * @return Video[]|Collection
     */
    public function getVideos(): array|Collection
    {
        return $this->videos;
    }

    public function setVideos(Collection|array $videos): void
    {
        $this->videos = $videos;
    }

    public function addVideo(Video $video): void
    {
        $this->videos->add($video);
    }


    /** @var RecordingSettingsBag[]|Collection */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: RecordingSettingsBag::class,
        cascade: ['persist']
    )]
    private array|Collection $recordingSettingsBags;


    /** @var RecordingRequest[]|Collection */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: RecordingRequest::class,
        cascade: ['persist']
    )]
    private array|Collection $recordingRequests;

    /**
     * @return RecordingRequest[]|Collection
     */
    public function getRecordingRequests(): array|Collection
    {
        return $this->recordingRequests;
    }

    public function addRecordingRequest(
        RecordingRequest $recordingRequest
    ): void
    {
        if (!$this->recordingRequests->contains($recordingRequest)) {
            $this->recordingRequests->add($recordingRequest);
        }
    }


    /** @var RecordingRequestResponse[]|Collection */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: RecordingRequestResponse::class,
        cascade: ['persist']
    )]
    private array|Collection $recordingRequestResponses;

    /**
     * @return RecordingRequestResponse[]|Collection
     */
    public function getRecordingRequestResponses(): array|Collection
    {
        return $this->recordingRequestResponses;
    }

    public function addRecordingRequestResponse(
        RecordingRequestResponse $recordingRequestResponse
    ): void
    {
        if (!$this->recordingRequestResponses->contains($recordingRequestResponse)) {
            $this->recordingRequestResponses->add($recordingRequestResponse);
        }
    }


    /** @var VideoMailing[]|Collection */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: VideoMailing::class,
        cascade: ['persist']
    )]
    private array|Collection $videoMailings;

    /**
     * @return VideoMailing[]|Collection
     */
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

    #[ORM\Column(
        type: Types::STRING,
        nullable: true,
        enumType: VideosListViewMode::class
    )]
    private ?VideosListViewMode $videosListViewMode = null;

    public function getVideosListViewMode(): VideosListViewMode
    {
        if (is_null($this->videosListViewMode)) {
            return VideosListViewMode::Tiles;
        }
        return $this->videosListViewMode;
    }

    public function setVideosListViewMode(
        VideosListViewMode $videosListViewMode
    ): void
    {
        $this->videosListViewMode = $videosListViewMode;
    }


    public function getUserIdentifier(): string
    {
        if (is_null($this->email)) {
            return $this->id;
        }

        return $this->email;
    }


    public function eraseCredentials(): void
    {
    }


    public function getFirstName(): ?string
    {
        if (!is_null($this->getThirdPartyAuthLinkedinResourceOwner())) {
            return $this->getThirdPartyAuthLinkedinResourceOwner()
                        ->getFirstName();
        }

        return null;
    }

    public function getLastName(): ?string
    {
        if (!is_null($this->getThirdPartyAuthLinkedinResourceOwner())) {
            return $this->getThirdPartyAuthLinkedinResourceOwner()
                        ->getLastName();
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function getNameOrEmail(): string
    {
        $nameOrEmail = $this->getFirstName();
        if (is_null($nameOrEmail)) {
            $nameOrEmail = $this->getLastName();
        } else {
            if (!is_null($this->getLastName())) {
                $nameOrEmail .= ' ' . $this->getLastName();
            }
            return $nameOrEmail;
        }

        if (is_null($nameOrEmail)) {
            if (is_null($this->getEmail())) {
                throw new Exception("No nameOrEmail for user '{$this->getId()}' because firstname, lastname, and email are all NULL.");
            }
            return $this->getEmail();
        } else {
            return $nameOrEmail;
        }
    }


    public function hasProfilePhoto(): bool
    {
        if (!is_null($this->getThirdPartyAuthLinkedinResourceOwner())
            && !is_null(
                $this->getThirdPartyAuthLinkedinResourceOwner()
                     ->getSortedProfilePicture800Url()
            )
        ) {
            return true;
        }

        return false;
    }

    public function getProfilePhotoUrl(): ?string
    {
        if ($this->hasProfilePhoto()) {
            return $this->getThirdPartyAuthLinkedinResourceOwner()
                        ->getSortedProfilePicture800Url();
        }

        return null;
    }

    public function getProfilePhotoContentType(): ?string
    {
        if ($this->hasProfilePhoto()) {
            return $this->getThirdPartyAuthLinkedinResourceOwner()
                        ->getSortedProfilePicture800ContentType();
        }

        return null;
    }
}
