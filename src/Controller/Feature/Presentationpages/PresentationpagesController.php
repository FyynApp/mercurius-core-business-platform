<?php

namespace App\Controller\Feature\Presentationpages;

use App\Entity\Feature\Presentationpages\Presentationpage;
use App\Entity\Feature\Recordings\Video;
use App\Form\Type\Feature\Presentationpages\PresentationpageType;
use App\Service\Feature\Presentationpages\PresentationpagesService;
use App\Service\Feature\Recordings\VideoService;
use App\Shared\Infrastructure\Controller\AbstractController;
use App\Shared\Presentation\Enum\FlashMessageLabel;
use App\VideoBasedMarketing\Account\Domain\Enum\VotingAttribute;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;


class PresentationpagesController
    extends AbstractController
{
    public function overviewAction(
        PresentationpagesService $presentationpagesService,
        VideoService             $videoService
    ): Response
    {
        return $this->render(
            'feature/presentationpages/overview.html.twig',
            [
                'PresentationpagesService' => $presentationpagesService,
                'VideoService' => $videoService
            ]
        );
    }

    public function createPageAction(
        VideoService        $videoService,
        TranslatorInterface $translator
    ): Response
    {
        if (sizeof($videoService->getAvailableVideos($this->getUser())) === 0) {
            $this->addFlash(FlashMessageLabel::Info->value, $translator->trans('feature.presentationpages.flash.need_to_create_video_to_create_presentationpage'));
        } else {
            $this->addFlash(FlashMessageLabel::Info->value, $translator->trans('feature.presentationpages.flash.need_choose_video_to_create_presentationpage'));
        }

        return $this->redirectToRoute(
            'feature.recordings.videos.overview'
        );
    }

    public function createTemplateAction(
        PresentationpagesService $presentationpagesService
    ): Response
    {
        $originalpresentationpage = $presentationpagesService->createTemplate($this->getUser());
        $draftPresentationpage = $presentationpagesService->createDraft($originalpresentationpage);

        return $this->redirectToRoute(
            'feature.presentationpages.editor',
            [
                'originalPresentationpageId' => $originalpresentationpage->getId(),
                'presentationpageId' => $draftPresentationpage->getId()
            ]
        );
    }

    public function createPageFromVideoFormAction(
        string                   $videoId,
        EntityManagerInterface   $entityManager,
        PresentationpagesService $presentationpagesService,
        VideoService             $videoService
    ): Response
    {
        $video = $entityManager->find(Video::class, $videoId);

        if (is_null($video)) {
            throw new NotFoundHttpException("No video with id '$videoId' found.");
        }

        $this->denyAccessUnlessGranted(VotingAttribute::Use->value, $video);

        $user = $this->getUser();

        if (!$presentationpagesService->userHasTemplates($user)) {
            $originalpresentationpage = $presentationpagesService->createPageFromVideo($video);
            $draftPresentationpage = $presentationpagesService->createDraft($originalpresentationpage);

            return $this->redirectToRoute(
                'feature.presentationpages.editor',
                [
                    'originalPresentationpageId' => $originalpresentationpage->getId(),
                    'presentationpageId' => $draftPresentationpage->getId()
                ]
            );
        }

        return $this->render(
            'feature/presentationpages/create_page_from_video_form.html.twig',
            [
                'video' => $video,
                'PresentationpagesService' => $presentationpagesService,
                'VideoService' => $videoService
            ]
        );
    }

    public function createPageFromVideoAndTemplateAction(
        string                   $videoId,
        string                   $templateId,
        PresentationpagesService $presentationpagesService,
        EntityManagerInterface   $entityManager
    ): Response
    {
        $video = $entityManager->find(Video::class, $videoId);

        if (is_null($video)) {
            throw new NotFoundHttpException("No video with id '$videoId' found.");
        }

        $this->denyAccessUnlessGranted(VotingAttribute::Use->value, $video);


        $template = $entityManager->find(Presentationpage::class, $templateId);

        if (is_null($templateId)) {
            throw new NotFoundHttpException("No presentationpage with id '$templateId' found.");
        }

        $this->denyAccessUnlessGranted(VotingAttribute::Use->value, $template);


        $originalPresentationpage = $presentationpagesService->createPageFromVideoAndTemplate($video, $template);
        $draftPresentationpage = $presentationpagesService->createDraft($originalPresentationpage);

        return $this->redirectToRoute(
            'feature.presentationpages.editor',
            [
                'originalPresentationpageId' => $originalPresentationpage->getId(),
                'presentationpageId' => $draftPresentationpage->getId()
            ]
        );
    }

    public function createDraftAction(
        string                   $presentationpageId,
        EntityManagerInterface   $entityManager,
        PresentationpagesService $presentationpagesService
    ): Response
    {
        $presentationpage = $entityManager->find(Presentationpage::class, $presentationpageId);

        if (is_null($presentationpage)) {
            throw new NotFoundHttpException("No presentationpage with id '$presentationpageId'.");
        }

        $this->denyAccessUnlessGranted(VotingAttribute::Edit->value, $presentationpage);

        $draft = $presentationpagesService->createDraft($presentationpage);

        return $this->redirectToRoute(
            'feature.presentationpages.editor',
            [
                'originalPresentationpageId' => $presentationpage->getId(),
                'presentationpageId' => $draft->getId()
            ]
        );
    }

    public function editorAction(
        string                   $presentationpageId,
        Request                  $request,
        EntityManagerInterface   $entityManager,
        PresentationpagesService $presentationpagesService
    ): Response
    {
        $presentationpage = $entityManager->find(Presentationpage::class, $presentationpageId);

        if (is_null($presentationpage)) {
            throw new NotFoundHttpException("No presentationpage with id '$presentationpageId'.");
        }

        $this->denyAccessUnlessGranted(VotingAttribute::Edit->value, $presentationpage);

        $form = $this->createForm(PresentationpageType::class, $presentationpage);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Presentationpage $presentationpage */
            $presentationpage = $form->getData();

            $presentationpagesService->handleEdited($presentationpage);

            return $this->redirectToRoute('feature.presentationpages.overview');
        } else {
            return $this->renderForm(
                'feature/presentationpages/editor.html.twig',
                [
                    'presentationpage' => $presentationpage,
                    'form' => $form,
                    'PresentationpagesService' => $presentationpagesService
                ]
            );
        }
    }

    public function previewAction(
        string                 $presentationpageId,
        EntityManagerInterface $entityManager,
        VideoService           $videoService
    ): Response
    {
        $presentationpage = $entityManager->find(Presentationpage::class, $presentationpageId);

        if (is_null($presentationpage)) {
            throw new NotFoundHttpException("No presentationpage with id '$presentationpageId' found.");
        }

        $this->denyAccessUnlessGranted(VotingAttribute::View->value, $presentationpage);

        return $this->render(
            'feature/presentationpages/preview.html.twig',
            [
                'presentationpage' => $presentationpage,
                'VideoService' => $videoService
            ]
        );
    }

    public function screenshotCaptureViewAction(
        string                   $presentationpageId,
        Request                  $request,
        EntityManagerInterface   $entityManager,
        VideoService             $videoService,
        PresentationpagesService $presentationpagesService
    ): Response
    {
        $presentationpage = $entityManager->find(Presentationpage::class, $presentationpageId);

        if (is_null($presentationpage)) {
            throw new NotFoundHttpException("No presentationpage with id '$presentationpageId' found.");
        }

        if (    $request->get('presentationpageHash')
            !== $presentationpagesService->generatePresentationpageHash($presentationpage)
        ) {
            throw new AccessDeniedHttpException("Hash {$request->get('presentationpageHash')} is invalid.");
        }

        {
            return $this->render(
                'feature/presentationpages/preview.html.twig',
                [
                    'presentationpage' => $presentationpage,
                    'VideoService' => $videoService
                ]
            );
        }
    }
}
