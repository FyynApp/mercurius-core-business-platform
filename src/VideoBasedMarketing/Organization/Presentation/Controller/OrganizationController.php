<?php

namespace App\VideoBasedMarketing\Organization\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
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

        return $this->render(
            '@videobasedmarketing.organization/organization/overview.html.twig',
            [
                'currentlyActiveOrganization' => $organizationDomainService
                    ->getCurrentlyActiveOrganizationOfUser($user),
                'currentlyActiveOrganization'
            ]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/organization/name',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/organisation/name',
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
}
