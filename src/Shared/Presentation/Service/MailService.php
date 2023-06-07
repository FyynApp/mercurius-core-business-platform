<?php

namespace App\Shared\Presentation\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Message;


class MailService
{
    private MailerInterface $mailer;

    private ContainerBagInterface $containerBag;


    public function __construct(
        MailerInterface       $mailer,
        ContainerBagInterface $containerBag
    )
    {
        $this->mailer = $mailer;
        $this->containerBag = $containerBag;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function send(
        Message $email,
        bool    $autoresponserProtection = true
    ): void
    {
        if ($autoresponserProtection) {
            // this non-standard header tells compliant autoresponders ("email holiday mode")
            // to not reply to this message because it's an automated email
            $email->setHeaders(
                $email
                    ->getHeaders()
                    ->addTextHeader(
                        'X-Auto-Response-Suppress',
                        'OOF, DR, RN, NRN, AutoReply'
                    )
            );
        }

        $this->mailer->send($email);
    }

    public function getDefaultSenderAddress(): Address
    {
        return new Address(
            $this->containerBag->get('app.mail.default_sender_address'),
            'Fyyn.io'
        );
    }
}
