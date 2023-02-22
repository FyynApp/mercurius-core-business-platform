<?php

namespace App\VideoBasedMarketing\Organization\Domain\Service;

use App\Shared\Domain\Enum\Iso639_1Code;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Organization\Domain\Entity\Invitation;
use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use App\VideoBasedMarketing\Organization\Presentation\Service\OrganizationPresentationService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Contracts\Translation\TranslatorInterface;


readonly class OrganizationDomainService
{
    public function __construct(
        private TranslatorInterface             $translator,
        private EntityManagerInterface          $entityManager,
        private OrganizationPresentationService $organizationPresentationService
    )
    {
    }

    public function userOwnsAnOrganization(
        User $user
    ): bool
    {
        return !is_null($user->getOwnedOrganization());
    }

    public function userIsMemberOfAnOrganization(
        User $user
    ): bool
    {
        return !is_null($user->getOrganization());
    }

    public function userCanCreateOrganization(
        User $user
    ): bool
    {
        if ($this->userIsMemberOfAnOrganization($user)) {
            return false;
        }

        return true;
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
        /** @var null|User $user */
        $user = $this
            ->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => trim(mb_strtolower($email))]);

        if (is_null($user)) {
            return true;
        }

        if ($this->userIsMemberOfAnOrganization($user)) {
            return false;
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public function inviteEmailToOrganization(
        string       $email,
        Organization $organization
    ): ?Invitation
    {
        $email = trim(mb_strtolower($email));
        if (!$this->emailCanBeInvitedToOrganization($email)) {
            return null;
        }

        $invitation = new Invitation($organization, $email);

        $this->organizationPresentationService->sendInvitationMail($invitation);
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
