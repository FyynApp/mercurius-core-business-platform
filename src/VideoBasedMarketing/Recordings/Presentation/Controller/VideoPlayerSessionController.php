<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Enum\AccessAttribute;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoPlayerSessionDomainService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class VideoPlayerSessionController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/recordings/videos/{videoId}/analytics',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/aufnahmen/videos/{videoId}/analyse',
        ],
        name        : 'videobasedmarketing.recordings.presentation.video_analytics',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function videoAnalyticsAction(
        string                          $videoId,
        VideoPlayerSessionDomainService $videoPlayerSessionDomainService
    ): Response
    {
        $r = $this->verifyAndGetOrganizationAndEntity(
            Video::class,
            $videoId,
            AccessAttribute::Use
        );

        /** @var Video $video */
        $video = $r->getEntity();

        return $this->render(
            '@videobasedmarketing.recordings/video_analytics.html.twig',
            [
                'video' => $video,

                'viewPercentagesPerSecond' => $videoPlayerSessionDomainService
                    ->getViewPercentagesPerSecond($video),

                'numberOfVideoPlayerSessions' => $videoPlayerSessionDomainService
                    ->getNumberOfVideoPlayerSessions($video),

                'numberOfStartedVideoPlayerSessions' => $videoPlayerSessionDomainService
                    ->getNumberOfStartedVideoPlayerSessions($video),

                'sessionAnalyticsInfos' => $videoPlayerSessionDomainService
                    ->getVideoPlayerSessionAnalyticsInfos($video)
            ]
        );
    }
}
