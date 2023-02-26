<?php

namespace App\VideoBasedMarketing\Organization\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Organization\Domain\Service\OrganizationDomainService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;


class OrganizationController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/organization/overview',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/organisation/übersicht',
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

        if (!$organizationDomainService->userIsMemberOfAnOrganization($user)) {
            $organizationDomainService->createOrganization($user);
        }

        return $this->render(
            '@videobasedmarketing.organization/organization/overview.html.twig',
            ['organization' => $organizationDomainService->getOrganizationOfUser($user)]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/organization/create',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/organisation/anlegen',
        ],
        name        : 'videobasedmarketing.organization.create',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function createAction(
        Request                   $request,
        OrganizationDomainService $organizationDomainService,
    ): Response
    {
        if (!$this->isCsrfTokenValid('create-organization', $request->request->get('csrf_token'))) {
            throw $this->createAccessDeniedException('CSRF token is invalid');
        }

        /** @var null|User $user */
        $user = $this->getUser();

        if ($organizationDomainService->userIsMemberOfAnOrganization($user)) {
            throw new BadRequestHttpException(
                "User '{$user->getId()}' is already associated with organization '{$organizationDomainService->getOrganizationOfUser($user)->getId()}'."
            );
        }

        $organizationDomainService->createOrganization($user);

        return $this->redirectToRoute('videobasedmarketing.organization.overview');
    }
}
