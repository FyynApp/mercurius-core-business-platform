<?php

namespace App\Entity\Feature\Recordings;

use App\Entity\Feature\Account\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'recording_settings', indexes: [])]
class RecordingSettings
{
    #[ORM\Id]
    #[ORM\OneToOne(inversedBy: 'recordingSettings', targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'users_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }


    #[ORM\Column(type: 'text', length: 32, unique: true)]
    private string $clientId = '';

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
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
