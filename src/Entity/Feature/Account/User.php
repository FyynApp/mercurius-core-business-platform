<?php

namespace App\Entity\Feature\Account;

use App\Entity\Feature\PresentationpageTemplates\PresentationpageTemplate;
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
        $this->presentationpageTemplates = new ArrayCollection();
    }


    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    public function getId(): ?string
    {
        return $this->id;
    }


    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }


    #[ORM\Column(type: 'json')]
    private array $roles = [];

    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }


    #[ORM\Column(type: 'string')]
    private string $password;

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }


    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }


    #[ORM\OneToOne(mappedBy: 'user', targetEntity: ThirdPartyAuthLinkedinResourceOwner::class, cascade: ['persist'])]
    private ?ThirdPartyAuthLinkedinResourceOwner $thirdPartyAuthLinkedinResourceOwner = null;

    public function getThirdPartyAuthLinkedinResourceOwner(): ?ThirdPartyAuthLinkedinResourceOwner
    {
        return $this->thirdPartyAuthLinkedinResourceOwner;
    }


    /** @var PresentationpageTemplate[]|Collection */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: PresentationpageTemplate::class, cascade: ['persist'])]
    private Collection $presentationpageTemplates;

    /**
     * @return PresentationpageTemplate[]|Collection
     */
    public function getPresentationpageTemplates(): Collection
    {
        return $this->presentationpageTemplates;
    }


    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }


    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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
