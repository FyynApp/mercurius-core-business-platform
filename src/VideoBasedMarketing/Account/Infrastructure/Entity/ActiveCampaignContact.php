<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\Entity;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use ValueError;


#[ORM\Entity]
#[ORM\Table(name: 'activecampaign_contacts')]
readonly class ActiveCampaignContact
{
    public function __construct(
        int  $id,
        User $user
    )
    {
        if ($id < 0) {
            throw new ValueError('id must be >= 0');
        }
        $this->id = $id;
        $this->user = $user;
        $user->setActiveCampaignContact($this);
    }

    #[ORM\Id]
    #[ORM\Column(
        type: 'integer',
        options: ['unsigned' => true]
    )]
    private int $id;

    public function getId(): int
    {
        return $this->id;
    }


    #[ORM\OneToOne(
        inversedBy: 'activeCampaignContact',
        targetEntity: User::class,
        cascade: ['persist']
    )]
    #[ORM\JoinColumn(
        name: 'users_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private User $user;

    public function getUser(): User
    {
        return $this->user;
    }
}
