<?php

namespace App\VideoBasedMarketing\Organization\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use App\VideoBasedMarketing\Organization\Domain\Service\OrganizationDomainService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;


class OrganizationController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/organizations/',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/organisationen/',
        ],
        name        : 'videobasedmarketing.organization.handle_create',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function handleCreateAction(
        Request                   $request,
        OrganizationDomainService $organizationDomainService
    ): Response
    {
        if (!$this->isCsrfTokenValid('create-new-organization', $request->get('_csrf_token'))) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        /** @var null|User $user */
        $user = $this->getUser();

        $org = $organizationDomainService->createOrganization($user);

        $organizationDomainService->switchOrganization(
            $user,
            $org
        );

        return $this->redirectToRoute('videobasedmarketing.organization.overview');
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/current-organization/overview',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/aktuelle-organisation/übersicht',
        ],
        name        : 'videobasedmarketing.organization.overview',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function overviewAction(
        OrganizationDomainService $organizationDomainService
    ): Response
    {
        /** @var null|User $user */
        $user = $this->getUser();

        return $this->render(
            '@videobasedmarketing.organization/organization/overview.html.twig',
            [
                'currentlyActiveOrganization' => $organizationDomainService
                    ->getCurrentlyActiveOrganizationOfUser($user)
            ]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/current-organization/name',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/aktuelle-organisation/name',
        ],
        name        : 'videobasedmarketing.organization.handle_name_edited',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function handleNameEdited(
        Request                $request,
        CapabilitiesService    $capabilitiesService,
        EntityManagerInterface $entityManager
    ): Response
    {
        if (!$this->isCsrfTokenValid('handle-organization-name-edited', $request->request->get('_csrf_token'))) {
            throw $this->createAccessDeniedException('CSRF token is invalid');
        }

        /** @var null|User $user */
        $user = $this->getUser();

        if (!$capabilitiesService->canEditOrganizationName($user)) {
            throw $this->createAccessDeniedException();
        } else {
            $user->getCurrentlyActiveOrganization()->setName(
                $request->get('name')
            );
            $entityManager->persist($user->getCurrentlyActiveOrganization());
            $entityManager->flush();
        }

        return $this->redirectToRoute('videobasedmarketing.organization.overview');
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/current-organization/switch',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/aktuelle-organisation/wechseln',
        ],
        name        : 'videobasedmarketing.organization.switch',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function switchAction(
        OrganizationDomainService $organizationDomainService
    ): Response
    {
        /** @var null|User $user */
        $user = $this->getUser();

        if (!$organizationDomainService->userCanSwitchOrganizations($user)) {
            return $this->redirectToRoute('videobasedmarketing.organization.overview');
        }

        return $this->render(
            '@videobasedmarketing.organization/organization/switch.html.twig',
            [
                'currentlyActiveOrganization' =>
                    $organizationDomainService->getCurrentlyActiveOrganizationOfUser($user),

                'organizationsUserCanSwitchTo' =>
                    $organizationDomainService->organizationsUserCanSwitchTo($user)
            ]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/organizations/{organizationId}/switch-to',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/organisations/{organizationId}/wechseln-zu',
        ],
        name        : 'videobasedmarketing.organization.handle_switch',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function handleSwitchAction(
        string                    $organizationId,
        OrganizationDomainService $organizationDomainService,
        EntityManagerInterface    $entityManager
    ): Response
    {
        /** @var null|User $user */
        $user = $this->getUser();

        $organizationDomainService->switchOrganization(
            $user,
            $entityManager->find(Organization::class, $organizationId)
        );

        return $this->redirectToRoute('videobasedmarketing.organization.overview');
    }
}
