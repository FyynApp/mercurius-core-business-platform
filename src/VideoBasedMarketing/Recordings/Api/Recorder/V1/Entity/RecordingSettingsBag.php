<?php

namespace App\VideoBasedMarketing\Recordings\Api\Recorder\V1\Entity;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
#[ORM\Table(name: 'recording_settings_bags')]
class RecordingSettingsBag
    implements UserOwnedEntityInterface
{
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    #[ORM\Id]
    #[ORM\Column(
        type: Types::STRING,
        length: 32
    )]
    private string $clientId = '';

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getId(): ?string
    {
        return $this->getClientId();
    }

    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'], inversedBy: 'recordingSettingsBags')]
    #[ORM\JoinColumn(name: 'users_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    public function getUser(): User
    {
        return $this->user;
    }


    #[ORM\Column(type: Types::TEXT)]
    private string $settings = '';

    public function getSettings(): string
    {
        return $this->settings;
    }

    public function setSettings(string $settings): void
    {
        $this->settings = $settings;
    }
}
