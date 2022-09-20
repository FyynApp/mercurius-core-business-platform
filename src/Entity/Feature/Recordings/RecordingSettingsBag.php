<?php

namespace App\Entity\Feature\Recordings;

use App\Entity\Feature\Account\UnregisteredClient;
use App\Entity\Feature\Account\User;
use App\Entity\Feature\Account\UserOrUnregisteredClientOwnedEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'recording_settings_bags')]
class RecordingSettingsBag extends UserOrUnregisteredClientOwnedEntity
{
    public function __construct(
        ?User $user,
        ?UnregisteredClient $unregisteredClient
    )
    {
        parent::__construct($user, $unregisteredClient);
    }


    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 32)]
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


    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'], inversedBy: 'recordingSettingsBags')]
    #[ORM\JoinColumn(name: 'users_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?User $user;


    #[ORM\ManyToOne(targetEntity: UnregisteredClient::class, cascade: ['persist'], inversedBy: 'recordingSessions')]
    #[ORM\JoinColumn(name: 'unregistered_clients_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?UnregisteredClient $unregisteredClient;
}
