<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\Entity;

use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\LingoSync\Domain\Enum\LingoSyncProcessTaskType;
use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use App\VideoBasedMarketing\Organization\Domain\Entity\OrganizationOwnedEntityInterface;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;


#[ORM\Entity]
#[ORM\Table(name: 'lingosync_process_tasks')]
#[ORM\Index(
    fields: ['createdAt'],
    name: 'created_at_idx'
)]
class LingoSyncProcessTask
    implements OrganizationOwnedEntityInterface
{
    /**
     * @throws Exception
     */
    public function __construct(
        LingoSyncProcess         $lingoSyncProcess,
        LingoSyncProcessTaskType $lingoSyncProcessTaskType,
        ?Bcp47LanguageCode       $targetLanguageBcp47LanguageCode
    )
    {
        $this->lingoSyncProcess = $lingoSyncProcess;
        $this->targetLanguageBcp47LanguageCode = $targetLanguageBcp47LanguageCode;
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
        targetEntity: LingoSyncProcess::class,
        cascade: ['persist'],
    )]
    #[ORM\JoinColumn(
        name: 'lingosync_processes_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private LingoSyncProcess $lingoSyncProcess;

    public function getLingoSyncProcess(): LingoSyncProcess
    {
        return $this->lingoSyncProcess;
    }


    #[ORM\Column(
        type: Types::STRING,
        length: 16,
        nullable: true,
        enumType: Bcp47LanguageCode::class
    )]
    private ?Bcp47LanguageCode $targetLanguageBcp47LanguageCode;

    public function getTargetLanguageBcp47LanguageCode(): Bcp47LanguageCode
    {
        return $this->targetLanguageBcp47LanguageCode;
    }

    public function getOrganization(): Organization
    {
        return $this->lingoSyncProcess->getOrganization();
    }
}
