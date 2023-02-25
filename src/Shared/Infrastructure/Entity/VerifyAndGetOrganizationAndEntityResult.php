<?php

namespace App\Shared\Infrastructure\Entity;

use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use App\VideoBasedMarketing\Organization\Domain\Entity\OrganizationOwnedEntityInterface;

class VerifyAndGetOrganizationAndEntityResult
{
    private Organization $organization;

    private OrganizationOwnedEntityInterface $entity;

    public function __construct(
        Organization $organization,
        OrganizationOwnedEntityInterface $entity
    )
    {
        $this->organization = $organization;
        $this->entity = $entity;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function getEntity(): OrganizationOwnedEntityInterface
    {
        return $this->entity;
    }
}
