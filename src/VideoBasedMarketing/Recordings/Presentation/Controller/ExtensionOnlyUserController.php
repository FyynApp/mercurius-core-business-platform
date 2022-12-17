<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class ExtensionOnlyUserController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/extension-only-user/recordings/videos/',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/nur-erweiterung-benutzer/aufnahmen/videos/',
        ],
        name        : 'videobasedmarketing.recordings.presentation.extension_only_user.videos.overview',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function videosOverviewAction(
        Request $request
    ): Response
    {
        return $this->render(
            '@videobasedmarketing.recordings/extension_only_user/videos_overview.html.twig',
            ['showEditModalForVideoId' => $request->get('showEditModalForVideoId')]
        );
    }
}
