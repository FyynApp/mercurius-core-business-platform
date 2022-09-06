<?php

namespace App\Security\Feature\Presentationpages;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Presentationpages\Presentationpage;
use App\Security\VotingAttribute;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PresentationpageVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [
            VotingAttribute::View->value,
            VotingAttribute::Edit->value
        ])) {
            return false;
        }

        if (!$subject instanceof Presentationpage) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Presentationpage $presentationpage */
        $presentationpage = $subject;

        switch (VotingAttribute::from($attribute)) {
            case VotingAttribute::View:
                return $this->canView($presentationpage, $user);
            case VotingAttribute::Edit:
            case VotingAttribute::Delete:
                return $this->canEdit($presentationpage, $user);
        }
    }

    private function canView(Presentationpage $presentationpage, User $user): bool
    {
        if ($this->canEdit($presentationpage, $user)) {
            return true;
        }

        return false;
    }

    private function canEdit(Presentationpage $presentationpage, User $user): bool
    {
        return $user->getId() === $presentationpage->getUser()->getId();
    }
}
