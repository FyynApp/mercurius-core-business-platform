<?php

namespace App\VideoBasedMarketing\Mailings\Presentation\Service;

use App\Shared\Presentation\Service\MailService;
use App\VideoBasedMarketing\Mailings\Domain\Entity\VideoMailing;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class MailingsPresentationService
{
    public function __construct(
        private MailService         $mailService,
        private TranslatorInterface $translator,
    )
    {
    }


    public function sendVideoMailing(
        VideoMailing $videoMailing
    ): void
    {
        $email = (new TemplatedEmail())
            ->from($videoMailing->getUser()->getEmail())
            ->replyTo($videoMailing->getUser()->getEmail())
            ->sender($this->mailService->getDefaultSenderAddress())
            ->to($videoMailing->getReceiverMailAddress())
            ->subject("{$this->translator->trans('video_mailing_editor.email.subject_prefix', [], 'videobasedmarketing.mailings')}{$videoMailing->getSubject()}")
            ->htmlTemplate(
                '@videobasedmarketing.mailings/video_mailing.email.html.twig'
            );

        $context = $email->getContext();
        $context['videoMailing'] = $videoMailing;

        $email->context($context);

        $this->mailService->send($email, false);
    }
}
