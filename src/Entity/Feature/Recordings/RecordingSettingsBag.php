<?php

namespace App\Entity\Feature\Recordings;

use App\Entity\Feature\Account\User;
use App\Entity\UserOwnedEntityInterface;
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
    #[ORM\Column(type: 'string', length: 32)]
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


    #[ORM\Column(type: 'text')]
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
