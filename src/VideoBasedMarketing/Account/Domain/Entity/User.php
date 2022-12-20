<?php

namespace App\VideoBasedMarketing\Account\Domain\Entity;

use App\VideoBasedMarketing\Account\Domain\Enum\Role;
use App\VideoBasedMarketing\Account\Infrastructure\Entity\ActiveCampaignContact;
use App\VideoBasedMarketing\Account\Infrastructure\Entity\ThirdPartyAuthLinkedinResourceOwner;
use App\VideoBasedMarketing\Account\Infrastructure\Repository\UserRepository;
use App\VideoBasedMarketing\Membership\Domain\Entity\Subscription;
use App\VideoBasedMarketing\Presentationpages\Domain\Entity\Presentationpage;
use App\VideoBasedMarketing\RecordingRequests\Domain\Entity\RecordingRequest;
use App\VideoBasedMarketing\RecordingRequests\Domain\Entity\RecordingRequestResponse;
use App\VideoBasedMarketing\Recordings\Api\Recorder\V1\Entity\RecordingSettingsBag;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;


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
        $this->email = $email;
    }


    #[ORM\Column(type: 'json')]
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


    #[ORM\Column(type: 'string')]
    private string $password;

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }


    #[ORM\Column(type: 'boolean')]
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

    /**
     * @throws Exception
     */
    public function makeRegistered(): void
    {
        if ($this->isRegistered()) {
            throw new Exception("User '{$this->getUserIdentifier()}' is already registered");
        }
        $this->removeRole(Role::UNREGISTERED_USER);
        $this->addRole(Role::REGISTERED_USER);
    }


    public function isExtensionOnly(): bool
    {
        return $this->hasRole(Role::EXTENSION_ONLY_USER);
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
