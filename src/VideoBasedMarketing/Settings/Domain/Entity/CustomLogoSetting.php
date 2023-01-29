<?php

namespace App\VideoBasedMarketing\Settings\Domain\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;

#[ORM\Entity]
#[ORM\Table(name: 'custom_logo_settings')]
#[ORM\Index(fields: ['createdAt'], name: 'created_at_idx')]
class CustomLogoSetting
{
    /**
     * @throws Exception
     */
    public function __construct(
        User $user
    )
    {
        $this->user = $user;
        $this->createdAt = DateAndTimeService::getDateTime();
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


    #[ORM\ManyToOne(
        targetEntity: User::class,
        cascade: ['persist'],
        inversedBy: 'customLogoSettings'
    )]
    #[ORM\JoinColumn(
        name: 'users_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private User $user;

    public function getuser(): User
    {
        return $this->user;
    }


    #[ORM\Column(type: 'datetime', nullable: false)]
    private DateTime $createdAt;

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }
}
