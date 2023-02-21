<?php

namespace App\VideoBasedMarketing\Organization\Domain\Service;

use App\Shared\Domain\Enum\Iso639_1Code;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Organization\Domain\Entity\Invitation;
use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use Symfony\Contracts\Translation\TranslatorInterface;


readonly class OrganizationDomainService
{
    public function __construct(
        private readonly TranslatorInterface $translator
    )
    {
    }

    public function userOwnsOrganization(
        User $user
    ): bool
    {

    }

    public function userIsMemberOfOrganization(
        User $user
    ): bool
    {

    }

    public function userCanCreateOrganization(
        User $user
    ): bool
    {
        if ($this->userIsMemberOfOrganization($user)) {
            return false;
        }
    }

    public function createOrganization(
        User $orgOwnerUser
    ): ?Organization
    {
        if (!$this->userCanCreateOrganization($orgOwnerUser)) {
            return null;
        }
    }

    public function emailCanBeInvitedToOrganization(
        string $email
    ): bool
    {

    }

    public function inviteEmailToOrganization(
        string $email
    ): ?Invitation
    {

    }

    public function acceptInvitation(
        Invitation $invitation
    ): bool
    {

    }

    public function getOrganizationName(
        Organization $organization,
        Iso639_1Code $iso639_1Code,
    ): string
    {
        if (is_null($organization->getName())) {
            return $this->translator->trans(
                'default_organization_name',
                [],
                'videobasedmarketing.organization',
                $iso639_1Code->value,
            );
        } else {
            return $organization->getName();
        }
    }
}
