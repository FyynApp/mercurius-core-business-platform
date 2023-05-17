<?php

namespace App\VideoBasedMarketing\Account\Domain\Security;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
use App\VideoBasedMarketing\Account\Domain\Enum\AccessAttribute;
use App\VideoBasedMarketing\Account\Domain\Service\AccessService;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\Organization\Domain\Entity\OrganizationOwnedEntityInterface;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoFolder;
use Exception;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;


class GeneralVoter
    extends Voter
{
    private CapabilitiesService $capabilitiesService;

    private AccessService $accessService;

    public function __construct(
        CapabilitiesService       $capabilitiesService,
        AccessService             $accessService
    )
    {
        $this->capabilitiesService = $capabilitiesService;
        $this->accessService       = $accessService;
    }

    protected function supports(
        string $attribute,
        mixed  $subject
    ): bool
    {
        $resolvedAttribute = AccessAttribute::tryFrom($attribute);
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
            && $attribute === AccessAttribute::Edit->value
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

        if (   $subject instanceof VideoFolder
            && !$subject->getIsVisibleForNonAdministrators()
            && !$this->capabilitiesService->canSeeFoldersNotVisibleForNonAdministrators($user)
        ) {
            return false;
        }

        if (   $subject instanceof OrganizationOwnedEntityInterface
            || $subject instanceof UserOwnedEntityInterface
        ) {
            if ($this->accessService->userCanAccessEntity(
                $user,
                AccessAttribute::from($attribute),
                $subject
            )) {
                return true;
            }
        }

        return false;
    }
}
