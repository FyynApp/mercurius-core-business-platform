<?php

namespace App\VideoBasedMarketing\Organization\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\Organization\Domain\Service\OrganizationDomainService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class GroupController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/organization/groups/move-to-administrators',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/organisation/gruppen/zu-administratoren-verschieben',
        ],
        name        : 'videobasedmarketing.organization.group.move_to_administrators',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function moveToAdministratorsAction(
        Request                   $request,
        OrganizationDomainService $organizationDomainService,
        EntityManagerInterface    $entityManager,
        CapabilitiesService       $capabilitiesService
    ): Response
    {
        /** @var null|User $user */
        $user = $this->getUser();

        if ($capabilitiesService->canMoveOrganizationMembersIntoGroups($user)) {
            $userIdToMove = $request->get('userId');
            /** @var null|User $userToMove */
            $userToMove = $entityManager->find(User::class, $userIdToMove);

            if (is_null($userToMove)) {
                throw $this->createNotFoundException("User with id '$userIdToMove' not found.");
            }

            if ($userToMove->getId() === $user->getId()) {
                throw $this->createAccessDeniedException(
                    "User to move '$userIdToMove' cannot move itself."
                );
            }

            if (!$organizationDomainService->userJoinedOrganization(
                $userToMove,
                $organizationDomainService->getCurrentlyActiveOrganizationOfUser($user))
            ) {
                throw $this->createAccessDeniedException(
                    "User to move '$userIdToMove' cannot be moved because they are not in the currently active organization of the user doing the move."
                );
            }

            $organizationDomainService->moveUserToAdministratorsGroup(
                $userToMove,
                $organizationDomainService->getCurrentlyActiveOrganizationOfUser($user)
            );

            return $this->redirectToRoute('videobasedmarketing.organization.overview');
        } else {
            throw $this->createAccessDeniedException();
        }
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/organization/groups/move-to-team-members',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/organisation/gruppen/zu-team-mitgliedern-verschieben',
        ],
        name        : 'videobasedmarketing.organization.group.move_to_team_members',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function moveToTeamMembersAction(
        Request                   $request,
        OrganizationDomainService $organizationDomainService,
        EntityManagerInterface    $entityManager,
        CapabilitiesService       $capabilitiesService
    ): Response
    {
        /** @var null|User $user */
        $user = $this->getUser();

        if ($capabilitiesService->canMoveOrganizationMembersIntoGroups($user)) {
            $userIdToMove = $request->get('userId');
            /** @var null|User $userToMove */
            $userToMove = $entityManager->find(User::class, $userIdToMove);

            if (is_null($userToMove)) {
                throw $this->createNotFoundException("User with id '$userIdToMove' not found.");
            }

            if ($userToMove->getId() === $user->getId()) {
                throw $this->createAccessDeniedException(
                    "User to move '$userIdToMove' cannot move itself."
                );
            }

            if (!$organizationDomainService->userJoinedOrganization(
                $userToMove,
                $organizationDomainService->getCurrentlyActiveOrganizationOfUser($user))
            ) {
                throw $this->createAccessDeniedException(
                    "User to move '$userIdToMove' cannot be moved because they are not in the currently active organization of the user doing the move."
                );
            }

            $organizationDomainService->moveUserToTeamMembersGroup(
                $userToMove,
                $organizationDomainService->getCurrentlyActiveOrganizationOfUser($user)
            );

            return $this->redirectToRoute('videobasedmarketing.organization.overview');
        } else {
            throw $this->createAccessDeniedException();
        }
    }
}
