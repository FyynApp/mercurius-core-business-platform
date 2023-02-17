<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\Entity;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
#[ORM\Table(name: 'thirdpartyauth_linkedin_resourceowners')]
class ThirdPartyAuthLinkedinResourceOwner
{
    public function __construct(
        string $id,
        string $email,
        string $firstName,
        string $lastName,
    )
    {
        $this->id = $id;
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->sortedProfilePicture800Url = null;
        $this->sortedProfilePicture800ContentType = null;
        $this->user = null;
    }

    #[ORM\Id]
    #[ORM\Column(type: 'string')]
    private string $id;

    public function getId(): string
    {
        return $this->id;
    }


    #[ORM\Column(type: 'string', length: 180, unique: true, nullable: false)]
    private string $email;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }


    #[ORM\Column(type: 'string', length: 256, unique: false, nullable: true)]
    private ?string $firstName;

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }


    #[ORM\Column(type: 'string', length: 256, unique: false, nullable: true)]
    private ?string $lastName;

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }


    #[ORM\Column(name: 'sorted_profile_picture_800_url', type: 'string', length: 2048, unique: true, nullable: true)]
    private ?string $sortedProfilePicture800Url;

    public function setSortedProfilePicture800Url(?string $url): void
    {
        $this->sortedProfilePicture800Url = $url;
    }

    public function getSortedProfilePicture800Url(): ?string
    {
        return $this->sortedProfilePicture800Url;
    }


    #[ORM\Column(name: 'sorted_profile_picture_800_content_type', type: 'string', length: 100, unique: false, nullable: true)]
    private ?string $sortedProfilePicture800ContentType;

    public function setSortedProfilePicture800ContentType(?string $contentType): void
    {
        $this->sortedProfilePicture800ContentType = $contentType;
    }

    public function getSortedProfilePicture800ContentType(): ?string
    {
        return $this->sortedProfilePicture800ContentType;
    }


    #[ORM\OneToOne(inversedBy: 'thirdPartyAuthLinkedinResourceOwner', targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'users_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $user;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }
}
