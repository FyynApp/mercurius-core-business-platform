<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class NativeBrowserRecorderController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/recordings/native-browser-recorder',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/aufnahmen/native-browser-recorder',
        ],
        name        : 'videobasedmarketing.recordings.presentation.show_native_browser_recorder',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function showNativeBrowserRecorderAction(): Response
    {
        return $this->render(
            '@videobasedmarketing.recordings/native_browser_recorder.html.twig'
        );
    }
}
