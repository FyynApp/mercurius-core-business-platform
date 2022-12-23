<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\Security;

use App\Shared\Presentation\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;


class EmailVerifier
{
    private VerifyEmailHelperInterface $verifyEmailHelper;

    private MailService $mailService;

    private EntityManagerInterface $entityManager;

    public function __construct(
        VerifyEmailHelperInterface $helper,
        MailService                $mailService,
        EntityManagerInterface     $manager
    )
    {
        $this->verifyEmailHelper = $helper;
        $this->mailService = $mailService;
        $this->entityManager = $manager;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendEmailAskingForVerification(
        string         $verifyEmailRouteName,
        UserInterface  $user,
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

    /**
     * @throws VerifyEmailExceptionInterface
     */
    public function handleVerification(
        Request       $request,
        UserInterface $user
    ): void
    {
        $this->verifyEmailHelper->validateEmailConfirmation(
            $request->getUri(),
            $user->getId(),
            $user->getEmail()
        );

        $user->setIsVerified(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
