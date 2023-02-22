<?php

namespace App\VideoBasedMarketing\Organization\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Organization\Domain\Service\OrganizationDomainService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class InvitationController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/organization/invitation/{invitationId}/accept',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/organisation/einladung/{invitationId}/annehmen',
        ],
        name        : 'videobasedmarketing.organization.invitation.accept',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function joinAction(
        string                    $invitationId,
        OrganizationDomainService $organizationDomainService
    ): Response
    {

    }
}
