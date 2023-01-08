<?php

namespace App\VideoBasedMarketing\Account\Domain\Security;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
use App\VideoBasedMarketing\Account\Domain\Enum\VotingAttribute;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;


class GeneralVoter
    extends Voter
{
    private CapabilitiesService $capabilitiesService;

    public function __construct(
        CapabilitiesService $capabilitiesService
    )
    {
        $this->capabilitiesService = $capabilitiesService;
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

        if ($subject instanceof UserOwnedEntityInterface) {
            return true;
        }

        return false;
    }

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

        /** @var UserOwnedEntityInterface $typedSubject */
        $typedSubject = $subject;

        if (    $typedSubject->getUser()->getId()
            === $user->getId()
        ) {
            return true;
        }

        return false;
    }
}
