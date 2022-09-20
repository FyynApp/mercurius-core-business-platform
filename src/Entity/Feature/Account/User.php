<?php

namespace App\Entity\Feature\Account;

use App\Entity\Feature\Presentationpages\Presentationpage;
use App\Entity\Feature\Recordings\RecordingSession;
use App\Entity\Feature\Recordings\RecordingSettingsBag;
use App\Entity\Feature\Recordings\Video;
use App\Repository\Feature\Account\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct()
    {
        $this->presentationpages = new ArrayCollection();
        $this->recordingSessions = new ArrayCollection();
        $this->recordingSettingsBags = new ArrayCollection();
        $this->videos = new ArrayCollection();
    }


    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(type: 'guid', unique: true)]
    private ?string $id = null;

    public function getId(): ?string
    {
        return $this->id;
    }


    #[ORM\Column(type: 'string', length: 180, unique: true, nullable: true)]
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
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
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
        return !is_null($this->email);
    }


    #[ORM\OneToOne(mappedBy: 'user', targetEntity: ThirdPartyAuthLinkedinResourceOwner::class, cascade: ['persist'])]
    private ?ThirdPartyAuthLinkedinResourceOwner $thirdPartyAuthLinkedinResourceOwner = null;

    public function getThirdPartyAuthLinkedinResourceOwner(): ?ThirdPartyAuthLinkedinResourceOwner
    {
        return $this->thirdPartyAuthLinkedinResourceOwner;
    }


    /** @var Presentationpage[]|Collection */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Presentationpage::class, cascade: ['persist'])]
    private array|Collection $presentationpages;

    /**
     * @return Presentationpage[]|Collection
     */
    public function getPresentationpages(): array|Collection
    {
        return $this->presentationpages;
    }


    /** @var RecordingSession[]|Collection */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: RecordingSession::class, cascade: ['persist'])]
    private array|Collection $recordingSessions;

    /**
     * @return RecordingSession[]|Collection
     */
    public function getRecordingSessions(): array|Collection
    {
        return $this->recordingSessions;
    }


    /** @var Video[]|Collection */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Video::class, cascade: ['persist'])]
    private array|Collection $videos;

    /**
     * @return Video[]|Collection
     */
    public function getVideos(): array|Collection
    {
        return $this->videos;
    }


    /** @var RecordingSettingsBag[]|Collection */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: RecordingSettingsBag::class, cascade: ['persist'])]
    private array|Collection $recordingSettingsBags;


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
            return $this->getThirdPartyAuthLinkedinResourceOwner()->getFirstName();
        }

        return null;
    }

    public function getLastName(): ?string
    {
        if (!is_null($this->getThirdPartyAuthLinkedinResourceOwner())) {
            return $this->getThirdPartyAuthLinkedinResourceOwner()->getLastName();
        }

        return null;
    }


    public function hasProfilePhoto(): bool
    {
        if (!is_null($this->getThirdPartyAuthLinkedinResourceOwner())
            && !is_null($this->getThirdPartyAuthLinkedinResourceOwner()->getSortedProfilePicture800Url())
        ) {
            return true;
        }

        return false;
    }

    public function getProfilePhotoUrl(): ?string
    {
        if ($this->hasProfilePhoto()) {
            return $this->getThirdPartyAuthLinkedinResourceOwner()->getSortedProfilePicture800Url();
        }

        return null;
    }

    public function getProfilePhotoContentType(): ?string
    {
        if ($this->hasProfilePhoto()) {
            return $this->getThirdPartyAuthLinkedinResourceOwner()->getSortedProfilePicture800ContentType();
        }

        return null;
    }
}
