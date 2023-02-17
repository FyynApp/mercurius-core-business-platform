<?php

namespace App\VideoBasedMarketing\Account\Presentation\Service;


use App\Shared\Presentation\Service\MailService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Infrastructure\Security\EmailVerifier;
use App\VideoBasedMarketing\Account\Infrastructure\Service\RequestParametersBasedUserAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use ValueError;

readonly class AccountPresentationService
{
    public function __construct(
        private MailService                           $mailService,
        private TranslatorInterface                   $translator,
        private EmailVerifier                         $emailVerifier,
        private RequestParametersBasedUserAuthService $requestParametersBasedUserAuthService,
        private EntityManagerInterface                $entityManager
    )
    {
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

    /**
     * @throws TransportExceptionInterface
     */
    public function sendPasswordResetEmail(
        string $email
    ): void
    {
        /** @var null|User $user */
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (is_null($user)) {
            return;
        }

        $url = $this->requestParametersBasedUserAuthService->createUrl(
            $user,
            'videobasedmarketing.account.presentation.password.change',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL,
            '+22 minutes'
        );

        $context['url'] = $url;

        $this->mailService->send(
            (new TemplatedEmail())
                ->from($this->mailService->getDefaultSenderAddress())
                ->to($user->getEmail())
                ->subject(
                    $this->translator->trans(
                        'sign_in.forgot_password.reset_password_email.subject',
                        [],
                        'videobasedmarketing.account'
                    )
                )
                ->htmlTemplate(
                    '@videobasedmarketing.account/sign_in/forgot_password/reset_password.email.html.twig'
                )
            ->context($context)
        );
    }
}
