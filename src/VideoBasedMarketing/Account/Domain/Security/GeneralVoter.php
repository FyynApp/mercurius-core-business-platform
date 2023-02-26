<?php

namespace App\VideoBasedMarketing\Account\Domain\Security;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
use App\VideoBasedMarketing\Account\Domain\Enum\VotingAttribute;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\Organization\Domain\Entity\OrganizationOwnedEntityInterface;
use App\VideoBasedMarketing\Organization\Domain\Service\OrganizationDomainService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Exception;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;


class GeneralVoter
    extends Voter
{
    private CapabilitiesService $capabilitiesService;

    private OrganizationDomainService $organizationDomainService;

    public function __construct(
        CapabilitiesService       $capabilitiesService,
        OrganizationDomainService $organizationDomainService
    )
    {
        $this->capabilitiesService = $capabilitiesService;
        $this->organizationDomainService = $organizationDomainService;
    }

    protected function supports(
        string $attribute,
        mixed  $subject
    ): bool
    {
        $resolvedAttribute = VotingAttribute::tryFrom($attribute);
        if (is_null($resolvedAttribute)) {
            return false;
        }

        if (   $subject instanceof UserOwnedEntityInterface
            || $subject instanceof OrganizationOwnedEntityInterface
        ) {
            return true;
        }

        return false;
    }

    /**
     * @throws Exception
     */
    protected function voteOnAttribute(
        string         $attribute,
        mixed          $subject,
        TokenInterface $token
    ): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (   $subject instanceof Video
            && $attribute === VotingAttribute::Edit->value
        ) {
            if (!$this->capabilitiesService->canEditVideos($user)) {
                return false;
            }
        }

        if (   $subject instanceof Video
            && $subject->isDeleted()
        ) {
            return false;
        }

        if ($subject instanceof UserOwnedEntityInterface) {
            $typedSubject = $subject;

            if (    $typedSubject->getUser()->getId()
                === $user->getId()
            ) {
                return true;
            }

            if ($this->organizationDomainService->userIsMemberOfAnOrganization($user)) {
                return $this->organizationDomainService->userOwnedEntityBelongsToOrganization(
                    $typedSubject,
                    $this->organizationDomainService->getOrganizationOfUser($user)
                );
            }
        }

        if ($subject instanceof OrganizationOwnedEntityInterface) {
            $typedSubject = $subject;

            if (    $typedSubject->getOrganization()->getId()
                === $this->organizationDomainService->getOrganizationOfUser($user)->getId()
            ) {
                return true;
            }
        }

        return false;
    }
}
