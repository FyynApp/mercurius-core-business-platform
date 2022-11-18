<?php

namespace App\VideoBasedMarketing\Presentationpages\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\Shared\Presentation\Enum\FlashMessageLabel;
use App\VideoBasedMarketing\Account\Domain\Enum\VotingAttribute;
use App\VideoBasedMarketing\Presentationpages\Domain\Entity\Presentationpage;
use App\VideoBasedMarketing\Presentationpages\Domain\Service\PresentationpagesService;
use App\VideoBasedMarketing\Presentationpages\Presentation\Form\Type\PresentationpageType;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoDomainService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;


class PresentationpagesController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/landingpages/overview',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/seiten/übersicht',
        ],
        name        : 'videobasedmarketing.presentationpages.presentation.overview',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function overviewAction(): Response
    {
        return $this->render(
            '@videobasedmarketing.presentationpages/overview.html.twig'
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/landingpages-of-type-page/',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/seiten-vom-typ-seite/',
        ],
        name        : 'videobasedmarketing.presentationpages.presentation.create_type_page',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function createPageAction(
        VideoDomainService  $videoDomainService,
        TranslatorInterface $translator
    ): Response
    {
        if (sizeof($videoDomainService->getAvailableVideos($this->getUser())) === 0) {
            $this->addFlash(
                FlashMessageLabel::Info->value,
                $translator->trans('flash.need_to_create_video_to_create_presentationpage')
            );
        } else {
            $this->addFlash(
                FlashMessageLabel::Info->value,
                $translator->trans('flash.need_choose_video_to_create_presentationpage')
            );
        }

        return $this->redirectToRoute(
            'videobasedmarketing.recordings.presentation.videos.overview'
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/landingpages-of-type-template/',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/seiten-vom-typ-vorlage/',
        ],
        name        : 'videobasedmarketing.presentationpages.presentation.create_type_template',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function createTemplateAction(
        PresentationpagesService $presentationpagesService
    ): Response
    {
        $originalpresentationpage = $presentationpagesService->createTemplate($this->getUser());
        $draftPresentationpage = $presentationpagesService->createDraft($originalpresentationpage);

        return $this->redirectToRoute(
            'videobasedmarketing.presentationpages.presentation.draft.editor',
            [
                'originalPresentationpageId' => $originalpresentationpage->getId(),
                'presentationpageId' => $draftPresentationpage->getId()
            ]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/landingpages/create-from-video-\'{videoId}\'-form',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/seiten/anlegen-basierend-auf-video-\'{videoId}\'-formular',
        ],
        name        : 'videobasedmarketing.presentationpages.presentation.create_page_from_video_form',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function createPageFromVideoFormAction(
        string                   $videoId,
        EntityManagerInterface   $entityManager,
        PresentationpagesService $presentationpagesService
    ): Response
    {
        $video = $entityManager->find(Video::class, $videoId);

        if (is_null($video)) {
            throw $this->createNotFoundException("No video with id '$videoId' found.");
        }

        $this->denyAccessUnlessGranted(VotingAttribute::Use->value, $video);

        $user = $this->getUser();

        if (!$presentationpagesService->userHasTemplates($user)) {
            $originalpresentationpage = $presentationpagesService->createPageFromVideo($video);
            $draftPresentationpage = $presentationpagesService->createDraft($originalpresentationpage);

            return $this->redirectToRoute(
                'videobasedmarketing.presentationpages.presentation.draft.editor',
                [
                    'originalPresentationpageId' => $originalpresentationpage->getId(),
                    'presentationpageId' => $draftPresentationpage->getId()
                ]
            );
        }

        return $this->render(
            '@videobasedmarketing.presentationpages/create_page_from_video_form.html.twig',
            [
                'video' => $video,
                'PresentationpagesService' => $presentationpagesService
            ]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/landingpages/create-from-video-\'{videoId}\'-and-template-\'{templateId}\'-form',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/seiten/anlegen-basierend-auf-video-\'{videoId}\'-und-vorlage-\'{templateId}\'-formular',
        ],
        name        : 'videobasedmarketing.presentationpages.presentation.create_page_from_video_and_template',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function createPageFromVideoAndTemplateAction(
        string                                                                             $videoId,
        string                                                                             $templateId,
        PresentationpagesService $presentationpagesService,
        EntityManagerInterface                                                             $entityManager
    ): Response
    {
        $video = $entityManager->find(Video::class, $videoId);

        if (is_null($video)) {
            throw $this->createNotFoundException("No video with id '$videoId' found.");
        }

        $this->denyAccessUnlessGranted(VotingAttribute::Use->value, $video);


        $template = $entityManager->find(Presentationpage::class, $templateId);

        if (is_null($templateId)) {
            throw $this->createNotFoundException("No presentationpage with id '$templateId' found.");
        }

        $this->denyAccessUnlessGranted(VotingAttribute::Use->value, $template);


        $originalPresentationpage = $presentationpagesService->createPageFromVideoAndTemplate($video, $template);
        $draftPresentationpage = $presentationpagesService->createDraft($originalPresentationpage);

        return $this->redirectToRoute(
            'videobasedmarketing.presentationpages.presentation.draft.editor',
            [
                'originalPresentationpageId' => $originalPresentationpage->getId(),
                'presentationpageId' => $draftPresentationpage->getId()
            ]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/landingpages/{presentationpageId}/drafts/',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/seiten/{presentationpageId}/entwürfe/',
        ],
        name        : 'videobasedmarketing.presentationpages.presentation.draft.create',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function createDraftAction(
        string                   $presentationpageId,
        EntityManagerInterface   $entityManager,
        PresentationpagesService $presentationpagesService
    ): Response
    {
        $presentationpage = $entityManager->find(Presentationpage::class, $presentationpageId);

        if (is_null($presentationpage)) {
            throw $this->createNotFoundException("No presentationpage with id '$presentationpageId'.");
        }

        $this->denyAccessUnlessGranted(VotingAttribute::Edit->value, $presentationpage);

        $draft = $presentationpagesService->createDraft($presentationpage);

        return $this->redirectToRoute(
            'videobasedmarketing.presentationpages.presentation.draft.editor',
            [
                'originalPresentationpageId' => $presentationpage->getId(),
                'presentationpageId' => $draft->getId()
            ]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/landingpages/{originalPresentationpageId}/drafts/{presentationpageId}/editor',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/seiten/{originalPresentationpageId}/entwürfe/{presentationpageId}/bearbeitungs-formular',
        ],
        name        : 'videobasedmarketing.presentationpages.presentation.draft.editor',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET, Request::METHOD_POST]
    )]
    public function editorAction(
        string                   $presentationpageId,
        Request                  $request,
        EntityManagerInterface   $entityManager,
        PresentationpagesService $presentationpagesService
    ): Response
    {
        $presentationpage = $entityManager->find(Presentationpage::class, $presentationpageId);

        if (is_null($presentationpage)) {
            throw $this->createNotFoundException("No presentationpage with id '$presentationpageId'.");
        }

        $this->denyAccessUnlessGranted(VotingAttribute::Edit->value, $presentationpage);

        $form = $this->createForm(PresentationpageType::class, $presentationpage);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \App\VideoBasedMarketing\Presentationpages\Domain\Entity\Presentationpage $presentationpage */
            $presentationpage = $form->getData();

            $presentationpagesService->handleEdited($presentationpage);

            return $this->redirectToRoute('videobasedmarketing.presentationpages.presentation.overview');
        } else {
            return $this->renderForm(
                '@videobasedmarketing.presentationpages/editor.html.twig',
                [
                    'presentationpage' => $presentationpage,
                    'form' => $form,
                    'PresentationpagesService' => $presentationpagesService
                ]
            );
        }
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/landingpages/{presentationpageId}/preview',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/seiten/{presentationpageId}/vorschau',
        ],
        name        : 'videobasedmarketing.presentationpages.presentation.preview',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function previewAction(
        string                 $presentationpageId,
        EntityManagerInterface $entityManager
    ): Response
    {
        $presentationpage = $entityManager->find(Presentationpage::class, $presentationpageId);

        if (is_null($presentationpage)) {
            throw $this->createNotFoundException("No presentationpage with id '$presentationpageId' found.");
        }

        $this->denyAccessUnlessGranted(VotingAttribute::View->value, $presentationpage);

        return $this->render(
            '@videobasedmarketing.presentationpages/preview.html.twig',
            [
                'presentationpage' => $presentationpage
            ]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/landingpages/{presentationpageId}/screenshot-capture-view',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/seiten/{presentationpageId}/bildschirmfoto-erstellen-ansicht',
        ],
        name        : 'videobasedmarketing.presentationpages.presentation.screenshot_capture_view',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function screenshotCaptureViewAction(
        string                     $presentationpageId,
        Request                    $request,
        EntityManagerInterface     $entityManager,
        PresentationpagesService   $presentationpagesService
    ): Response
    {
        $presentationpage = $entityManager->find(Presentationpage::class, $presentationpageId);

        if (is_null($presentationpage)) {
            throw $this->createNotFoundException("No presentationpage with id '$presentationpageId' found.");
        }

        if (    $request->get('presentationpageHash')
            !== $presentationpagesService->generatePresentationpageHash($presentationpage)
        ) {
            throw new AccessDeniedHttpException("Hash {$request->get('presentationpageHash')} is invalid.");
        }

        {
            return $this->render(
                '@videobasedmarketing.presentationpages/preview.html.twig',
                [
                    'presentationpage' => $presentationpage
                ]
            );
        }
    }
}
