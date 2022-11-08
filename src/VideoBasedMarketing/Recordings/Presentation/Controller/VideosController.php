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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;


class VideosController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/recordings/videos/',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/aufnahmen/videos/',
        ],
        name        : 'videobasedmarketing.recordings.presentation.videos.overview',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function videosOverviewAction(
        Request $request
    ): Response
    {
        return $this->render(
            '@videobasedmarketing.recordings/videos_overview.html.twig',
            ['showEditModalForVideoId' => $request->get('showEditModalForVideoId')]
        );
    }

    #[Route(
        path        : '/v/{videoShortId}',
        name        : 'videobasedmarketing.recordings.presentation.video.share_link',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function videoShareLinkAction(
        string $videoShortId,
        EntityManagerInterface $entityManager
    ): Response
    {
        /** @var EntityRepository $r */
        $r = $entityManager->getRepository(Video::class);

        /** @var null|Video $video */
        $video = $r->findOneBy(['shortId' => $videoShortId]);

        if (is_null($video)) {
            throw new NotFoundHttpException("No video with short id '$videoShortId' found.");
        }

        return $this->redirectToRoute(
            'videobasedmarketing.recordings.presentation.video.show_with_video_only_presentationpage_template',
            ['videoId' => $video->getId()]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/recordings/videos/{videoId}/wvopt',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/aufnahmen/videos/{videoId}/wvopt',
        ],
        name        : 'videobasedmarketing.recordings.presentation.video.show_with_video_only_presentationpage_template',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function showWithVideoOnlyPresentationpageTemplateAction(
        string $videoId,
        EntityManagerInterface $entityManager
    ): Response
    {
        /** @var null|Video $video */
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

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/recordings/videos/{videoId}/deletion',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/aufnahmen/videos/{videoId}/löschung',
        ],
        name        : 'videobasedmarketing.recordings.presentation.video.deletion',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function deleteVideoAction(
        string $videoId,
        EntityManagerInterface $entityManager,
        VideoService $videoService,
        TranslatorInterface $translator
    ): Response
    {
        /** @var null|Video $video */
        $video = $entityManager->find(Video::class, $videoId);

        if (is_null($video)) {
            throw new NotFoundHttpException("No video with id '$videoId'.");
        }

        $this->denyAccessUnlessGranted(VotingAttribute::Delete->value, $video);

        $videoService->deleteVideo($video);

        $this->addFlash(
            FlashMessageLabel::Success->value,
            $translator->trans(
                'video_successfully_deleted',
                ['title' => $video->getTitle()],
                'videobasedmarketing.recordings'
            )
        );

        return $this->redirectToRoute('videobasedmarketing.recordings.presentation.videos.overview');
    }
}
