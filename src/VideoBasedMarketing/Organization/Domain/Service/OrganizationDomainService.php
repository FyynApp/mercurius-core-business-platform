<?php

namespace App\VideoBasedMarketing\Organization\Domain\Service;

use App\Shared\Domain\Enum\Iso639_1Code;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Entity\UserOwnedEntityInterface;
use App\VideoBasedMarketing\Account\Domain\Service\AccountDomainService;
use App\VideoBasedMarketing\Organization\Domain\Entity\Group;
use App\VideoBasedMarketing\Organization\Domain\Entity\Invitation;
use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use App\VideoBasedMarketing\Organization\Domain\Enum\AccessRight;
use App\VideoBasedMarketing\Organization\Presentation\Service\OrganizationPresentationService;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use ValueError;


readonly class OrganizationDomainService
{
    public function __construct(
        private TranslatorInterface             $translator,
        private EntityManagerInterface          $entityManager,
        private OrganizationPresentationService $organizationPresentationService,
        private AccountDomainService            $accountDomainService
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

    public function userCanCreateOrManageOrganization(
        User $user
    ): bool
    {
        if ($this->userOwnsAnOrganization($user)) {
            return true;
        }

        if (!$this->userIsMemberOfAnOrganization($user)) {
            return true;
        }

        return false;
    }

    public function getOrganizationOfUser(
        User $user
    ): Organization
    {
        if (!is_null($user->getOwnedOrganization())) {
            return $user->getOwnedOrganization();
        }

        if (!is_null($user->getOrganization())) {
            return $user->getOrganization();
        }

        return $this->createOrganization($user);
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

    /**
     * @throws Exception
     */
    public function createOrganization(
        User $owningUser
    ): Organization
    {
        if (!$this->userCanCreateOrganization($owningUser)) {
            throw new ValueError("User '{$owningUser->getId()}' cannot create organization.");
        }

        $organization = new Organization($owningUser);

        $adminGroup = new Group(
            $organization,
            'Administrators',
            [AccessRight::FULL_ACCESS],
            false
        );

        $this->entityManager->persist($adminGroup);

        $teamMemberGroup = new Group(
            $organization,
            'Team Members',
            [],
            true
        );

        $this->entityManager->persist($teamMemberGroup);

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

    /**
     * @throws Exception
     */
    public function acceptInvitation(
        Invitation $invitation,
        ?User      $user
    ): ?User
    {
        if (!is_null($user) && $user->isRegistered()) {

            if (!is_null($user->getOwnedOrganization())) {
                return null;
            }

            if (!is_null($user->getOrganization())) {
                if ($user->getOrganization()->getId() === $invitation->getOrganization()->getId()) {
                    return $user;
                }
            }

            if ($user->getEmail() !== $invitation->getEmail()) {

                /** @var ObjectRepository<User> $repo */
                $repo = $this->entityManager->getRepository(User::class);

                /** @var null|User $userForInvitationEmail */
                $userForInvitationEmail = $repo->findOneBy(['email' => $invitation->getEmail()]);

                if (!is_null($userForInvitationEmail)) {
                    return null;
                }
            }
        } else {
            $user = $this->accountDomainService->createRegisteredUser(
                $invitation->getEmail()
            );
        }

        $defaultGroup = $this->getDefaultGroupForNewMembers(
            $invitation->getOrganization()
        );

        $user->setOrganization($invitation->getOrganization());
        $defaultGroup->addMember($user);
        $this->entityManager->persist($user);
        $this->entityManager->persist($defaultGroup);
        $this->entityManager->flush();

        $this->entityManager->refresh($invitation->getOrganization());

        $this->entityManager->remove($invitation);
        unset($invitation);
        $this->entityManager->flush();

        return $user;
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

    /** @return User[] */
    public function getUsersOfOrganization(
        Organization $organization
    ): array
    {
        $users = [$organization->getOwningUser()];

        /** @var ObjectRepository<User> $repo */
        $repo = $this->entityManager->getRepository(User::class);

        return array_merge($users, $repo->findBy(
            ['organization' => $organization]
        ));
    }

    public function userOwnedEntityBelongsToOrganization(
        UserOwnedEntityInterface $entity,
        Organization             $organization
    ): bool
    {
        $user = $entity->getUser();

        if (!$this->userIsMemberOfAnOrganization($user)) {
            return false;
        }

        $entityOrganisation = $this->getOrganizationOfUser($user);

        return $entityOrganisation->getId() === $organization->getId();
    }

    public function getGroupName(
        Group        $group,
        Iso639_1Code $iso639_1Code,
    ): string
    {
        return $this->translator->trans(
            "group.name.{$group->getName()}",
            [],
            'videobasedmarketing.organization',
            $iso639_1Code->value,
        );
    }


    /** @return Group[] */
    public function getGroups(
        Organization $organization
    ): array
    {
        /** @var ObjectRepository<Group> $repo */
        $repo = $this->entityManager->getRepository(Group::class);

        return $repo->findBy(
            ['organization' => $organization],
            ['createdAt' => Criteria::DESC]
        );
    }

    /** @return Group[] */
    public function getGroupsOfUser(
        User $user
    ): array
    {
        $organization = $this->getOrganizationOfUser($user);

        /** @var ObjectRepository<Group> $repo */
        $repo = $this->entityManager->getRepository(Group::class);

        /** @var Group[] $allGroups */
        $allGroups = $repo->findBy(
            ['organization' => $organization],
            ['createdAt' => Criteria::DESC]
        );

        /** @var Group[] $foundGroups */
        $foundGroups = [];
        foreach ($allGroups as $group) {
            foreach ($group->getMembers() as $member) {
                if ($member->getId() === $user->getId()) {
                    $foundGroups[] = $group;
                }
            }
        }

        return $foundGroups;
    }


    /**
     * @throws Exception
     */
    public function getDefaultGroupForNewMembers(
        Organization $organization
    ): Group
    {
        /** @var ObjectRepository<Group> $repo */
        $repo = $this->entityManager->getRepository(Group::class);

        /** @var Group|null $group */
        $group = $repo->findOneBy(
            [
                'organization' => $organization,
                'isDefaultForNewMembers' => true
            ]
        );

        if (is_null($group)) {
            throw new Exception(
                "Organization '{$organization->getId()}' does not have default group for new members."
            );
        }

        return $group;
    }

    /** @return User[] */
    public function getGroupMembers(
        Group $group
    ): array
    {
        return $group->getMembers();
    }

    public function moveUserToAdministratorsGroup(
        User  $user
    ): void {
        $groups = $this->getGroups(
            $this->getOrganizationOfUser($user)
        );

        foreach ($groups as $group) {
            if ($group->isAdministratorsGroup()) {
                $group->addMember($user);
            } else {
                $group->removeMember($user);
            }
            $this->entityManager->persist($group);
        }

        $this->entityManager->flush();
    }

    public function moveUserToTeamMembersGroup(
        User  $user
    ): void {
        $groups = $this->getGroups(
            $this->getOrganizationOfUser($user)
        );

        foreach ($groups as $group) {
            if ($group->isTeamMembersGroup()) {
                $group->addMember($user);
            } else {
                $group->removeMember($user);
            }
            $this->entityManager->persist($group);
        }

        $this->entityManager->flush();
    }

    public function userHasAccessRight(
        User        $user,
        AccessRight $accessRight
    ): bool
    {
        if (!$this->userIsMemberOfAnOrganization($user)) {
            return false;
        }

        if (    $this->getOrganizationOfUser($user)->getOwningUser()->getId()
            === $user->getId()
        ) {
            return true;
        }

        foreach ($this->getGroupsOfUser($user) as $group) {
            foreach ($group->getAccessRights() as $groupAccessRight) {
                if (   $groupAccessRight === AccessRight::FULL_ACCESS
                    || $groupAccessRight === $accessRight
                ) {
                    return true;
                }
            }
        }

        return false;
    }
}
