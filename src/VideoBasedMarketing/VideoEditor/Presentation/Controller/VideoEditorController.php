<?php

namespace App\VideoBasedMarketing\VideoEditor\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VideoEditorController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/video-editor/{videoId}',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/video-bearbeitung/{videoId}',
        ],
        name        : 'videobasedmarketing.video_editor.presentation.video_editor',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function videoEditorAction(): Response
    {
        return $this->render('@videobasedmarketing.video_editor/video_editor.html.twig');
    }
}
