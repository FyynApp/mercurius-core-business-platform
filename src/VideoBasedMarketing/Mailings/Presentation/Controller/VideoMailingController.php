<?php

namespace App\VideoBasedMarketing\Mailings\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\Shared\Presentation\Enum\FlashMessageLabel;
use App\VideoBasedMarketing\Account\Domain\Enum\AccessAttribute;
use App\VideoBasedMarketing\Mailings\Domain\Entity\VideoMailing;
use App\VideoBasedMarketing\Mailings\Domain\Service\VideoMailingDomainService;
use App\VideoBasedMarketing\Mailings\Presentation\Service\MailingsPresentationService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;


class VideoMailingController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/mailings/video-mailings/',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/mailings/video-botschaften/',
        ],
        name        : 'videobasedmarketing.mailings.presentation.create_video_mailing',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function createVideoMailingAction(
        Request                   $request,
        VideoMailingDomainService $videoMailingDomainService
    ): Response
    {
        $r = $this->verifyAndGetUserAndEntity(
            Video::class,
            $request->get('videoId'),
            AccessAttribute::Use
        );

        /** @var Video $video */
        $video = $r->getEntity();

        $videoMailing = $videoMailingDomainService->createVideoMailing($video);

        return $this->redirectToRoute(
            'videobasedmarketing.mailings.presentation.show_video_mailing_editor',
            [
                'videoMailingId' => $videoMailing->getId(),
            ]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/mailings/video-mailings/{videoMailingId}/form',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/mailings/video-botschaften/{videoMailingId}/formular',
        ],
        name        : 'videobasedmarketing.mailings.presentation.show_video_mailing_editor',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function showVideoMailingEditorAction(
        string $videoMailingId
    ): Response
    {
        $r = $this->verifyAndGetUserAndEntity(
            VideoMailing::class,
            $videoMailingId,
            AccessAttribute::Edit
        );

        return $this->render(
            '@videobasedmarketing.mailings/video_mailing_editor.html.twig',
            ['videoMailing' => $r->getEntity()]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/mailings/video-mailings/{videoMailingId}/delivery',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/mailings/video-botschaften/{videoMailingId}/versand',
        ],
        name        : 'videobasedmarketing.mailings.presentation.send_video_mailing',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function sendVideoMailingAction(
        string                      $videoMailingId,
        MailingsPresentationService $mailingsPresentationService,
        TranslatorInterface         $translator
    ): Response
    {
        $r = $this->verifyAndGetUserAndEntity(
            VideoMailing::class,
            $videoMailingId,
            AccessAttribute::Use
        );

        /** @var VideoMailing $videoMailing */
        $videoMailing = $r->getEntity();

        $mailingsPresentationService->sendVideoMailing($videoMailing);

        $this->addFlash(
            FlashMessageLabel::Success->value,
            $translator->trans('video_mailing_editor.email_sent_successfully_flash_message', [], 'videobasedmarketing.mailings')
        );

        return $this->redirectToRoute(
            'videobasedmarketing.mailings.presentation.show_video_mailing_editor',
            [
                'videoMailingId' => $videoMailing->getId(),
            ]
        );
    }
}
