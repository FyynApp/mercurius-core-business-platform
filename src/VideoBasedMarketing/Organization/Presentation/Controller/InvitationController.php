<?php

namespace App\VideoBasedMarketing\Organization\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\Shared\Presentation\Enum\FlashMessageLabel;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Organization\Domain\Service\OrganizationDomainService;
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
        TranslatorInterface       $translator
    ): Response
    {
        /** @var null|User $user */
        $user = $this->getUser();

        if (!$organizationDomainService->userOwnsAnOrganization($user)) {
            throw $this->createAccessDeniedException(
                "User '{$user->getId()}' does not own an organization."
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
            $user->getOwnedOrganization()
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
        methods     : [Request::METHOD_GET]
    )]
    public function acceptInvitationAction(
        string                    $invitationId,
        OrganizationDomainService $organizationDomainService
    ): Response
    {
        return $this->redirectToRoute('videobasedmarketing.organization.overview');
    }
}
