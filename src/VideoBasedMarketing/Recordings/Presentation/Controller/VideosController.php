<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\Shared\Presentation\Enum\FlashMessageLabel;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Enum\AccessAttribute;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoFolder;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoDomainService;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoFolderDomainService;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoPlayerSessionDomainService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        Request                  $request,
        VideoFolderDomainService $videoFolderDomainService
    ): Response
    {
        $videoFolderId = $request->get('videoFolderId');

        $videoFolder = null;

        if (!is_null($videoFolderId)) {
            $r = $this->verifyAndGetUserAndEntity(
                VideoFolder::class,
                $videoFolderId,
                AccessAttribute::View
            );

            /** @var VideoFolder $videoFolder */
            $videoFolder = $r->getEntity();

            $user = $r->getUser();
        } else {
            /** @var User $user */
            $user = $this->getUser();
        }

        return $this->render(
            '@videobasedmarketing.recordings/videos_overview.html.twig',
            [
                'q' => trim($request->get('q', '')),

                'videoFolders' => $videoFolderDomainService
                    ->getAvailableVideoFoldersForCurrentlyActiveOrganization(
                        $user,
                        $videoFolder
                    ),

                'videoFolder' => $videoFolder,

                'showEditModalForVideoId' => $request->get('showEditModalForVideoId')
            ]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/v/{videoShortId}',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/v/{videoShortId}',
        ],
        name        : 'videobasedmarketing.recordings.presentation.video.share_link',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function videoShareLinkAction(
        string                          $videoShortId,
        EntityManagerInterface          $entityManager,
        VideoDomainService              $videoDomainService,
        Request                         $request,
        CapabilitiesService             $capabilitiesService,
        VideoPlayerSessionDomainService $videoPlayerSessionDomainService
    ): Response
    {
        /** @var EntityRepository $r */
        $r = $entityManager->getRepository(Video::class);

        /** @var null|Video $video */
        $video = $r->findOneBy(['shortId' => $videoShortId]);

        if (is_null($video)) {
            throw $this->createNotFoundException("No video with short id '$videoShortId' found.");
        }

        if (!$videoDomainService->videoCanBeShownOnPresentationpage($video)) {
            return $this->redirectToRoute('shared.presentation.contentpages.homepage');
        }

        $customDomain = $request->headers->get('X-Mercurius-Custom-Domain');

        if (!is_null($customDomain) && $customDomain !== $_ENV['ROUTER_REQUEST_CONTEXT_HOST']) {
            if (!$capabilitiesService->canPresentLandingpageOnCustomDomain($video->getUser())) {
                return $this->redirectToRoute('shared.presentation.contentpages.homepage');
            }
        }

        $videoDomainService->prepareForShowingWithVideoOnlyPresentationpageTemplate($video);

        if (is_null($video->getVideoOnlyPresentationpageTemplate())) {
            throw $this->createNotFoundException("Video '{$video->getId()}' does not have a video only presentationpage template.");
        }

        $videoPlayerSession = $videoPlayerSessionDomainService->createVideoPlayerSession(
            $this->getUser(),
            $video,
            (string)$request->getClientIp()
        );

        return $this->render(
            '@videobasedmarketing.recordings/video_landingpage.html.twig',
            [
                'video' => $video,
                'videoPlayerSession' => $videoPlayerSession
            ]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/recordings/videos/{videoId}/deletion',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/aufnahmen/videos/{videoId}/löschung',
        ],
        name        : 'videobasedmarketing.recordings.presentation.video.deletion',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function deleteVideoAction(
        string              $videoId,
        TranslatorInterface $translator,
        VideoDomainService  $videoDomainService
    ): Response
    {
        $r = $this->verifyAndGetUserAndEntity(
            Video::class,
            $videoId,
            AccessAttribute::Delete
        );

        /** @var Video $video */
        $video = $r->getEntity();

        $videoDomainService->deleteVideo($video);

        $this->addFlash(
            FlashMessageLabel::Success->value,
            $translator->trans(
                'video_successfully_deleted',
                ['title' => $video->getTitle()],
                'videobasedmarketing.recordings'
            )
        );

        return $this
            ->redirectToRoute(
                'videobasedmarketing.recordings.presentation.videos.overview'
            );
    }
}
