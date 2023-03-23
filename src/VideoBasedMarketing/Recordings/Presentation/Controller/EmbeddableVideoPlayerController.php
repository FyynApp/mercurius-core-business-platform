<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Enum\AccessAttribute;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoPlayerSessionDomainService;
use App\VideoBasedMarketing\Recordings\Infrastructure\Enum\AssetMimeType;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;


class EmbeddableVideoPlayerController
    extends AbstractController
{
    #[Route(
        path        : 'embed/video-player/{videoShortId}/data.jsonp',
        name        : 'videobasedmarketing.recordings.presentation.embeddable_video_player.data',
        methods     : [Request::METHOD_GET]
    )]
    public function dataAction(
        string $videoShortId
    ): Response
    {
        return new Response(
            null,
            Response::HTTP_OK,
            ['Content-Type' => 'text/javascript']
        );
    }

    #[Route(
        path        : 'embed/video-player/{videoShortId}/style.css',
        name        : 'videobasedmarketing.recordings.presentation.embeddable_video_player.style',
        methods     : [Request::METHOD_GET]
    )]
    public function styleAction(
        string                 $videoShortId,
        EntityManagerInterface $entityManager
    ): Response
    {
        /** @var ObjectRepository<Video> $r */
        $r = $entityManager->getRepository(Video::class);

        /** @var null|Video $video */
        $video = $r->findOneBy(['shortId' => $videoShortId]);

        if (is_null($video)) {
            throw $this->createNotFoundException("No video with short id '$videoShortId' found.");
        }

        return $this->render(
            '@videobasedmarketing.recordings/embeddable_video_player/style.css.twig',
            ['video' => $video],
            new Response(
                null,
                Response::HTTP_OK,
                ['Content-Type' => 'text/css']
            )
        );
    }

    #[Route(
        path        : 'embed/video-player/{videoShortId}/script.js',
        name        : 'videobasedmarketing.recordings.presentation.embeddable_video_player.script',
        methods     : [Request::METHOD_GET]
    )]
    public function scriptAction(
        string                          $videoShortId,
        Request                         $request,
        EntityManagerInterface          $entityManager,
        VideoPlayerSessionDomainService $videoPlayerSessionDomainService
    ): Response
    {
        /** @var ObjectRepository<Video> $r */
        $r = $entityManager->getRepository(Video::class);

        /** @var null|Video $video */
        $video = $r->findOneBy(['shortId' => $videoShortId]);

        if (is_null($video)) {
            throw $this->createNotFoundException("No video with short id '$videoShortId' found.");
        }

        $videoPlayerSession = $videoPlayerSessionDomainService->createVideoPlayerSession(
            $this->getUser(),
            $video,
            (string)$request->getClientIp()
        );

        return $this->render(
            '@videobasedmarketing.recordings/embeddable_video_player/script.js.twig',
            ['video' => $video, 'videoPlayerSession' => $videoPlayerSession],
            new Response(
                null,
                Response::HTTP_OK,
                ['Content-Type' => 'text/javascript']
            )
        );
    }
}
