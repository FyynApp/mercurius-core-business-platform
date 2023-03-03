<?php

namespace App\VideoBasedMarketing\Organization\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\Shared\Presentation\Enum\FlashMessageLabel;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\Account\Infrastructure\Service\RequestParametersBasedUserAuthService;
use App\VideoBasedMarketing\Organization\Domain\Entity\Invitation;
use App\VideoBasedMarketing\Organization\Domain\Service\OrganizationDomainService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;


class InvitationController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/organization/invitations/',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/organisation/einladungen/',
        ],
        name        : 'videobasedmarketing.organization.invitation.send',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function sendInvitationAction(
        Request                   $request,
        OrganizationDomainService $organizationDomainService,
        TranslatorInterface       $translator,
        CapabilitiesService       $capabilitiesService
    ): Response
    {
        /** @var null|User $user */
        $user = $this->getUser();

        if (!$capabilitiesService->canInviteOrganizationMembers($user)) {
            throw $this->createAccessDeniedException(
                "User '{$user->getId()}' cannot invite organization members."
            );
        }

        $email = $request->get('email');

        if (is_null($email) || trim($email) === '') {
            throw new BadRequestHttpException("email is empty.");
        }

        $violations = Validation::createValidator()->validate($email, new Email());

        if ($violations->count() > 0) {
            throw new BadRequestHttpException("Invalid email '$email'.");
        }

        $invitation = $organizationDomainService->inviteEmailToOrganization(
            $email,
            $organizationDomainService->getCurrentlyActiveOrganizationOfUser($user)
        );

        if (is_null($invitation)) {
            $this->addFlash(
                FlashMessageLabel::Warning->value,
                $translator->trans(
                    'invitation.sent_impossible',
                    ['{email}' => $email],
                    'videobasedmarketing.organization'
                )
            );
        } else {
            $this->addFlash(
                FlashMessageLabel::Success->value,
                $translator->trans(
                    'invitation.sent_success',
                    ['{email}' => $email],
                    'videobasedmarketing.organization'
                )
            );
        }

        return $this->redirectToRoute('videobasedmarketing.organization.overview');
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/organization/invitations/{invitationId}/accept',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/organisation/einladungen/{invitationId}/annehmen',
        ],
        name        : 'videobasedmarketing.organization.invitation.accept',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET, Request::METHOD_POST]
    )]
    public function acceptInvitationAction(
        string                                $invitationId,
        Request                               $request,
        OrganizationDomainService             $organizationDomainService,
        EntityManagerInterface                $entityManager,
        TranslatorInterface                   $translator,
        RequestParametersBasedUserAuthService $requestParametersBasedUserAuthService
    ): Response
    {
        /** @var null|Invitation $invitation */
        $invitation = $entityManager->find(Invitation::class, $invitationId);

        if (is_null($invitation)) {
            $this->redirectToRoute('shared.presentation.contentpages.homepage');
        }

        if ($request->getMethod() === Request::METHOD_GET) {
            return $this->render(
                '@videobasedmarketing.organization/invitation/ask_accept.html.twig',
                ['invitation' => $invitation]
            );
        }


        $user = $organizationDomainService->acceptInvitation(
            $invitation,
            $this->getUser()
        );

        if (!is_null($user)) {
            $this->addFlash(
                FlashMessageLabel::Success->value,
                $translator->trans(
                    'invitation.accept.success',
                    [],
                    'videobasedmarketing.organization'
                )
            );

            return $requestParametersBasedUserAuthService->createRedirectResponse(
                $user,
                'videobasedmarketing.organization.invitation.accepted'
            );

        } else {
            $this->addFlash(
                FlashMessageLabel::Warning->value,
                $translator->trans(
                    'invitation.accept.impossible',
                    [],
                    'videobasedmarketing.organization'
                )
            );

            return $this->redirectToRoute(
                'videobasedmarketing.organization.invitation.accept',
                ['invitationId' => $invitation->getId()]
            );
        }
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/organization/invitations/accepted',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/organisation/einladungen/angenommen',
        ],
        name        : 'videobasedmarketing.organization.invitation.accepted',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function acceptedInvitationAction(): Response
    {
        return $this->redirectToRoute('videobasedmarketing.recordings.presentation.videos.overview');
    }
}
