<?php

namespace App\Controller\Feature\Recordings;

use App\Entity\Feature\Recordings\Video;
use App\Form\Type\Feature\Recordings\VideoType;
use App\Security\VotingAttribute;
use App\Service\Feature\Presentationpages\PresentationpagesService;
use App\Service\Feature\Recordings\VideoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class VideosController extends AbstractController
{
    public function videosOverviewAction(VideoService $videoService): Response
    {
        return $this->render(
            'feature/recordings/videos_overview.html.twig',
            ['VideoService' => $videoService]
        );
    }

    public function videoEditFormAction(
        string                   $videoId,
        Request                  $request,
        PresentationpagesService $presentationpagesService,
        VideoService             $videoService,
        EntityManagerInterface   $entityManager
    ): Response
    {
        /** @var null|Video $video */
        $video = $entityManager->find(Video::class, $videoId);

        if (is_null($video)) {
            throw new NotFoundHttpException("Cannot find video with id '$videoId'.");
        }

        $this->denyAccessUnlessGranted(VotingAttribute::Edit->value, $video);

        $form = $this->createForm(VideoType::class, $video);
        $form->handleRequest($request);

        return $this->render(
            'feature/recordings/video_edit_form.html.twig',
            [
                'video' => $video,
                'PresentationpagesService' => $presentationpagesService,
                'VideoService' => $videoService
            ]
        );
    }

    public function videoShareLinkAction(
        string $videoShortId
    ): Response
    {

    }
}
