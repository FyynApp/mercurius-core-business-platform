<?php

namespace App\VideoBasedMarketing\Organization\Domain\Service;

use App\Shared\Domain\Enum\Iso639_1Code;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Organization\Domain\Entity\Invitation;
use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use App\VideoBasedMarketing\Organization\Presentation\Service\OrganizationPresentationService;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
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
        if (   is_null($user->getOwnedOrganization())
            && is_null($user->getOrganization())
        ) {
            return false;
        }

        return true;
    }

    public function getOrganizationOfUser(
        User $user
    ): ?Organization
    {
        if (!is_null($user->getOwnedOrganization())) {
            return $user->getOwnedOrganization();
        }

        if (!is_null($user->getOrganization())) {
            return $user->getOrganization();
        }

        return null;
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
     * @throws Exception|TransportExceptionInterface
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

        /** @var ObjectRepository<Invitation> $repo */
        $repo = $this->entityManager->getRepository(Invitation::class);

        /** @var null|Invitation $invitation */
        $invitation = $repo->findOneBy(['email' => $email]);

        if (is_null($invitation)) {
            $invitation = new Invitation($organization, $email);
            $this->entityManager->persist($invitation);
            $this->entityManager->flush();
        } else {
            if ($invitation->getOrganization()->getId() !== $organization->getId()) {
                return null;
            }
        }

        $this->organizationPresentationService->sendInvitationMail($invitation);

        return $invitation;
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

    public function hasPendingInvitations(
        Organization $organization
    ): bool
    {
        return sizeof($this->getPendingInvitations($organization)) > 0;
    }

    /** @return Invitation[] */
    public function getPendingInvitations(
        Organization $organization
    ): array
    {
        /** @var ObjectRepository<Invitation> $repo */
        $repo = $this->entityManager->getRepository(Invitation::class);

        return $repo->findBy(
            ['organization' => $organization],
            ['createdAt' => Criteria::DESC]
        );
    }
}
