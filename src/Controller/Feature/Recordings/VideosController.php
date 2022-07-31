<?php

namespace App\Controller\Feature\Recordings;

use App\Service\Feature\Recordings\VideoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class VideosController extends AbstractController
{
    public function videosOverviewAction(VideoService $videoService): Response
    {
        return $this->render(
            'feature/recordings/videos_overview.html.twig',
            ['VideoService' => $videoService]
        );
    }
}
