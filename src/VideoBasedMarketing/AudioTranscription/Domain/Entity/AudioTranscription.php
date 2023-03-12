<?php

namespace App\VideoBasedMarketing\AudioTranscription\Domain\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Entity\HappyScribeTranscription;
use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use App\VideoBasedMarketing\Organization\Domain\Entity\OrganizationOwnedEntityInterface;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use ValueError;


#[ORM\Entity]
#[ORM\Table(name: 'custom_logo_settings')]
#[ORM\Index(fields: ['createdAt'], name: 'created_at_idx')]
class AudioTranscription
    implements OrganizationOwnedEntityInterface, UserOwnedEntityInterface
{
    /**
     * @throws Exception
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->setOrganization($user->getCurrentlyActiveOrganization());
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
        type: Types::DATETIME_MUTABLE,
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

    public function setUser(User $user): void
    {
        $this->user = $user;
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


    /** @var HappyScribeTranscription[]|Collection */
    #[ORM\OneToMany(
        mappedBy: 'audioTranscription',
        targetEntity: HappyScribeTranscription::class,
        cascade: ['persist']
    )]
    private array|Collection $happyScribeTranscriptions;

    /**
     * @return HappyScribeTranscription[]|Collection
     */
    public function getHappyScribeTranscriptions(): array|Collection
    {
        return $this->happyScribeTranscriptions;
    }

    public function addHappyScribeTranscription(
        HappyScribeTranscription $happyScribeTranscription
    ): void
    {
        foreach ($this->happyScribeTranscriptions as $existingHappyScribeTranscription) {
            if ($existingHappyScribeTranscription->getId() === $happyScribeTranscription->getId()) {
                throw new ValueError(
                    "Happy Scribe transcription '{$happyScribeTranscription->getId()}' already in list of Happy Scribe transcriptions of audio transcription '{$this->getId()}'."
                );
            }
        }

        $this->happyScribeTranscriptions->add($happyScribeTranscription);
    }


    #[ORM\Column(
        type: Types::TEXT,
        nullable: true
    )]
    private ?string $webVtt = null;

    public function setWebVtt(string $webVtt): void
    {
        $this->webVtt = $webVtt;
    }

    public function getWebVtt(): ?string
    {
        return $this->webVtt;
    }
}
