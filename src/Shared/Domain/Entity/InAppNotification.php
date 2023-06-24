<?php

namespace App\Shared\Domain\Entity;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use App\VideoBasedMarketing\Organization\Domain\Entity\OrganizationOwnedEntityInterface;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;


#[ORM\Entity]
#[ORM\Table(name: 'in_app_notifications')]
#[ORM\Index(
    fields: ['createdAt'],
    name: 'created_at_idx'
)]
class InAppNotification
    implements OrganizationOwnedEntityInterface
{
    /**
     * @throws Exception
     */
    public function __construct(
        string       $message,
        ?string      $targetUrl,
        Organization $organization,
        User         $user
    )
    {
        $this->createdAt = DateAndTimeService::getDateTimeImmutable();
        $this->message = mb_substr($message, 0, 8192);
        $this->targetUrl = is_null($targetUrl) ? null : mb_substr($targetUrl, 0, 8192);
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
        type: Types::DATETIME_IMMUTABLE,
        nullable: false
    )]
    private DateTimeImmutable $createdAt;

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    
    #[ORM\Column(
        type: Types::STRING,
        length: 8192,
        nullable: false
    )]
    private readonly string $message;

    public function getMessage(): string
    {
        return $this->message;
    }


    #[ORM\Column(
        type: Types::STRING,
        length: 8192,
        nullable: true
    )]
    private readonly ?string $targetUrl;

    public function getTargetUrl(): ?string
    {
        return $this->targetUrl;
    }
}
