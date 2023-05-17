<?php

namespace App\VideoBasedMarketing\Mailings\Presentation\Service;

use App\Shared\Presentation\Service\MailService;
use App\VideoBasedMarketing\Mailings\Domain\Entity\VideoMailing;
use App\VideoBasedMarketing\Recordings\Infrastructure\Enum\AssetMimeType;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class MailingsPresentationService
{
    private string $kernelProjectDir;

    public function __construct(
        private MailService                     $mailService,
        private TranslatorInterface             $translator,
        private RecordingsInfrastructureService $recordingsInfrastructureService,
        ContainerBagInterface                   $containerBag,
    )
    {
        $this->kernelProjectDir = $containerBag->get('kernel.project_dir');
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
            ->addPart(
                (
                    new DataPart(
                        fopen(
                            $this->kernelProjectDir
                                . DIRECTORY_SEPARATOR
                                . 'public'
                                . DIRECTORY_SEPARATOR
                                . 'generated-content'
                                . DIRECTORY_SEPARATOR
                            . $this->recordingsInfrastructureService->getVideoPosterStillWithPlayOverlayForEmailAssetRelativeFilePath($videoMailing->getVideo()),
                            'r'
                        ), 'videoPreview', AssetMimeType::ImagePng->value
                    )
                )->asInline()
            )
            ->htmlTemplate(
                '@videobasedmarketing.mailings/video_mailing.email.html.twig'
            );

        $context = $email->getContext();
        $context['videoMailing'] = $videoMailing;

        $email->context($context);

        $this->mailService->send($email, false);
    }
}
