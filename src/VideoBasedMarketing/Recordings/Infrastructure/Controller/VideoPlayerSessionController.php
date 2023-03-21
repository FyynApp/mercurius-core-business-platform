<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoPlayerSession;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoPlayerSessionDomainService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;


class VideoPlayerSessionController
    extends AbstractController
{
    #[Route(
        path        : 'recordings/video-player-session/{videoPlayerSessionId}/events/',
        name        : 'videobasedmarketing.recordings.track_video_player_session_event',
        methods     : [Request::METHOD_POST]
    )]
    public function trackEventAction(
        string                          $videoPlayerSessionId,
        EntityManagerInterface          $entityManager,
        VideoPlayerSessionDomainService $videoPlayerSessionDomainService,
        Request                         $request
    ): Response
    {
        /** @var null|VideoPlayerSession $session */
        $session = $entityManager->find(
            VideoPlayerSession::class,
            $videoPlayerSessionId
        );

        if (is_null($session)) {
            throw $this->createNotFoundException("No video player session with id '$videoPlayerSessionId'.");
        }

        $playerCurrentTime = $request->get('playerCurrentTime');
        if (is_null($playerCurrentTime)) {
            throw new BadRequestHttpException("Missing request parameter 'playerCurrentTime'.");
        }

        $videoPlayerSessionDomainService->trackEvent(
            $session,
            (float)$playerCurrentTime
        );

        return new Response(
            null,
            Response::HTTP_CREATED,
            [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'POST',
                'Access-Control-Allow-Headers' => 'DNT,X-CustomHeader,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type'
            ]
        );
    }
}
