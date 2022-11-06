<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Controller;

use App\Shared\Presentation\Enum\FlashMessageLabel;
use App\VideoBasedMarketing\Account\Domain\Enum\VotingAttribute;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;


class VideosController
    extends AbstractController
{
    public function videosOverviewAction(
        Request $request
    ): Response
    {
        return $this->render(
            '@videobasedmarketing.recordings/videos_overview.html.twig',
            ['showEditModalForVideoId' => $request->get('showEditModalForVideoId')]
        );
    }

    public function videoShareLinkAction(
        string $videoShortId,
        EntityManagerInterface $entityManager
    ): Response
    {
        /** @var EntityRepository $r */
        $r = $entityManager->getRepository(Video::class);

        /** @var null|\App\VideoBasedMarketing\Recordings\Domain\Entity\Video $video */
        $video = $r->findOneBy(['shortId' => $videoShortId]);

        if (is_null($video)) {
            throw new NotFoundHttpException("No video with short id '$videoShortId' found.");
        }

        return $this->redirectToRoute(
            'feature.recordings.video.show_with_video_only_presentationpage_template',
            ['videoId' => $video->getId()]
        );
    }

    public function showWithVideoOnlyPresentationpageTemplateAction(
        string $videoId,
        EntityManagerInterface $entityManager
    ): Response
    {
        /** @var null|\App\VideoBasedMarketing\Recordings\Domain\Entity\Video $video */
        $video = $entityManager->find(Video::class, $videoId);

        if (is_null($video)) {
            throw new NotFoundHttpException("No video with id '$videoId'.");
        }

        if (is_null($video->getVideoOnlyPresentationpageTemplate())) {
            throw new NotFoundHttpException("Video '$videoId' does not have a video only presentationpage template.");
        }

        return $this->render(
            '@videobasedmarketing.recordings/video_show_with_video_only_presentationpage_template.html.twig',
            ['video' => $video]
        );
    }

    public function deleteVideoAction(
        string $videoId,
        EntityManagerInterface $entityManager,
        VideoService $videoService,
        TranslatorInterface $translator
    ): Response
    {
        /** @var null|\App\VideoBasedMarketing\Recordings\Domain\Entity\Video $video */
        $video = $entityManager->find(Video::class, $videoId);

        if (is_null($video)) {
            throw new NotFoundHttpException("No video with id '$videoId'.");
        }

        $this->denyAccessUnlessGranted(VotingAttribute::Delete->value, $video);

        $videoService->deleteVideo($video);

        $this->addFlash(
            FlashMessageLabel::Success->value,
            $translator->trans(
                'feature.recordings.video_successfully_deleted',
                ['title' => $video->getTitle()]
            )
        );

        return $this->redirectToRoute('feature.recordings.videos.overview');
    }
}
