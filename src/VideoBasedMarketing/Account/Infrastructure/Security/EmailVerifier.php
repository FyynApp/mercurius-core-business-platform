<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\Security;

use App\Shared\Presentation\Service\MailService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;


class EmailVerifier
{
    private VerifyEmailHelperInterface $verifyEmailHelper;

    private MailService $mailService;

    public function __construct(
        VerifyEmailHelperInterface $helper,
        MailService                $mailService
    )
    {
        $this->verifyEmailHelper = $helper;
        $this->mailService = $mailService;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendEmailAskingForVerification(
        string         $verifyEmailRouteName,
        User           $user,
        TemplatedEmail $email
    ): void
    {
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            $verifyEmailRouteName,
            $user->getId(),
            $user->getEmail(),
            ['id' => $user->getId()]
        );

        $context = $email->getContext();
        $context['signedUrl'] = $signatureComponents->getSignedUrl();
        $context['expiresAtMessageKey'] = $signatureComponents->getExpirationMessageKey();
        $context['expiresAtMessageData'] = $signatureComponents->getExpirationMessageData();

        $email->context($context);

        $this->mailService->send($email);
    }
}
