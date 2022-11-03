<?php

namespace App\Security;

use App\BoundedContext\Account\Domain\Entity\User;
use App\BoundedContext\Account\Domain\Entity\UserOwnedEntityInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;


class GeneralVoter
    extends Voter
{
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

        /** @var UserOwnedEntityInterface $typedSubject */
        $typedSubject = $subject;

        if ($typedSubject->getUser()
                         ->getId() === $user->getId()) {
            return true;
        }

        return false;
    }
}
