<?php

namespace App\Entity\Feature\Recordings;

use App\Entity\Feature\Account\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;

#[ORM\Entity]
#[ORM\Table(name: 'recording_sessions')]
class RecordingSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    public function getId(): ?string
    {
        return $this->id;
    }


    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'], inversedBy: 'recordingSessions')]
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
}
