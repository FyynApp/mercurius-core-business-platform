<?php

namespace App\VideoBasedMarketing\Account\Presentation\Service;


use App\Shared\Presentation\Service\MailService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Infrastructure\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Contracts\Translation\TranslatorInterface;
use ValueError;

class AccountPresentationService
{
    private MailService $mailService;

    private TranslatorInterface $translator;

    private EmailVerifier $emailVerifier;

    public function __construct(
        MailService         $mailService,
        TranslatorInterface $translator,
        EmailVerifier       $emailVerifier
    )
    {
        $this->mailService = $mailService;
        $this->translator = $translator;
        $this->emailVerifier = $emailVerifier;
    }

    public function sendVerificationEmailForClaimedUser(
        User $user
    ): void
    {
        if (!$user->isRegistered()) {
            throw new ValueError(
                'User is not registered.'
            );
        }

        if ($user->isVerified()) {
            throw new ValueError(
                'User is already verified.'
            );
        }

        $this->emailVerifier->sendEmailAskingForVerification(
            'videobasedmarketing.account.presentation.sign_up.email_verification',
            $user,
            (new TemplatedEmail())
                ->from($this->mailService->getDefaultSenderAddress())
                ->to($user->getEmail())
                ->subject(
                    $this->translator->trans(
                        'claim_unregistered_user.form.cta',
                        [],
                        'videobasedmarketing.account'
                    )
                )
                ->htmlTemplate(
                    '@videobasedmarketing.account/claim_unregistered_user/verify_claimed_account.email.html.twig'
                )
        );
    }
}
