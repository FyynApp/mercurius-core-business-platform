<?php

namespace App\VideoBasedMarketing\Account\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
use App\VideoBasedMarketing\Account\Domain\Enum\AccessAttribute;
use App\VideoBasedMarketing\Organization\Domain\Entity\OrganizationOwnedEntityInterface;
use App\VideoBasedMarketing\Organization\Domain\Service\OrganizationDomainService;
use Exception;


readonly class AccessService
{
    public function __construct(
        private OrganizationDomainService $organizationDomainService
    )
    {}

    /**
     * @throws Exception
     */
    public function userCanAccessEntity(
        User                                                      $user,
        AccessAttribute                                           $accessAttribute,
        UserOwnedEntityInterface|OrganizationOwnedEntityInterface $entity
    ): bool
    {
        if ($entity instanceof OrganizationOwnedEntityInterface) {
            foreach ($this->organizationDomainService->getAllOrganizationsForUser($user) as $organization) {
                if (    $entity->getOrganization()->getId()
                    === $organization->getId()
                ) {
                    return true;
                }
            }
        }

        if ($entity instanceof UserOwnedEntityInterface) {
            if ($entity->getUser()->getId() === $user->getId()) {
                return true;
            }
        }

        return false;
    }
}
