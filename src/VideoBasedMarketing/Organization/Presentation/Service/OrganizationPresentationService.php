<?php

namespace App\VideoBasedMarketing\Organization\Presentation\Service;

use App\Shared\Presentation\Service\MailService;
use App\VideoBasedMarketing\Organization\Domain\Entity\Invitation;
use Exception;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class OrganizationPresentationService
{
    public function __construct(
        private MailService         $mailService,
        private TranslatorInterface $translator,
        private RouterInterface     $router
    )
    {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function sendInvitationMail(
        Invitation $invitation
    ): void
    {
        $context = [
            'acceptUrl' => $this->router->generate(
                'videobasedmarketing.organization.invitation.accept',
                ['invitationId' => $invitation->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'owningUserName' => $invitation->getOrganization()->getOwningUser()->getNameOrEmail()
        ];

        $this->mailService->send(
            (new TemplatedEmail())
                ->from($this->mailService->getDefaultSenderAddress())
                ->to($invitation->getEmail())
                ->subject(
                    $this->translator->trans(
                        'invitation.email.subject',
                        ['owningUserName' => $invitation->getOrganization()->getOwningUser()->getNameOrEmail()],
                        'videobasedmarketing.organization'
                    )
                )
                ->htmlTemplate(
                    '@videobasedmarketing.organization/invitation/invitation.email.html.twig'
                )
                ->context($context)
        );
    }
}
