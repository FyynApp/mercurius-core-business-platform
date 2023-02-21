<?php

namespace App\VideoBasedMarketing\Organization\Domain\Service;

use App\Shared\Domain\Enum\Iso639_1Code;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Organization\Domain\Entity\Invitation;
use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


readonly class OrganizationDomainService
{
    public function __construct(
        private TranslatorInterface    $translator,
        private EntityManagerInterface $entityManager
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
        User $owningUser
    ): ?Organization
    {
        if (!$this->userCanCreateOrganization($owningUser)) {
            return null;
        }

        $organization = new Organization($owningUser);
        $this->entityManager->persist($organization);
        $this->entityManager->flush();
        $this->entityManager->refresh($owningUser);

        return $organization;
    }

    public function emailCanBeInvitedToOrganization(
        string $email
    ): bool
    {
        return true;
    }

    public function inviteEmailToOrganization(
        string       $email,
        Organization $organization
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
