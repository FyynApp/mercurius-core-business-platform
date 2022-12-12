<?php

namespace App\Shared\Presentation\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;


class MailService
{
    private MailerInterface $mailer;

    public function __construct(
        MailerInterface $mailer
    )
    {
        $this->mailer = $mailer;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendEmail(
        string $to,
        string $subject,
        string $htmlTemplate,
        array  $context
    ): void
    {
        $email = new TemplatedEmail();

        $email
            ->to($to)
            ->subject($subject)
            ->htmlTemplate($htmlTemplate)
            ->context($context);

        // this non-standard header tells compliant autoresponders ("email holiday mode")
        // to not reply to this message because it's an automated email
        $email->setHeaders(
            $email
                ->getHeaders()
                ->addTextHeader(
                    'X-Auto-Response-Suppress',
                    'OOF, DR, RN, NRN, AutoReply')
        );

        $this->mailer->send($email);
    }
}
