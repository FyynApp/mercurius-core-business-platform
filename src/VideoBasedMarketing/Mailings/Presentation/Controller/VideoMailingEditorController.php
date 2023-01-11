<?php

namespace App\VideoBasedMarketing\Mailings\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Enum\VotingAttribute;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class VideoMailingEditorController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/mailings/video/{videoId}/mailing-editor',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/mailings/aufnahme/{videoId}/mailing-editor',
        ],
        name        : 'videobasedmarketing.mailings.presentation.show_video_mailing_editor',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function showVideoMailingEditorAction(
        string $videoId
    ): Response
    {
        $r = $this->verifyAndGetUserAndEntity(
            Video::class,
            $videoId,
            VotingAttribute::Use
        );

        return $this->render(
            '@videobasedmarketing.mailings/video_mailing_editor.html.twig',
            ['video' => $r->getEntity()]
        );
    }
}
