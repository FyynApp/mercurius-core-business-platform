<?php

namespace App\Controller\Feature\Presentationpages;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Presentationpages\Presentationpage;
use App\Entity\Feature\PresentationpageTemplates\PresentationpageTemplate;
use App\Entity\Feature\Recordings\Video;
use App\Service\Feature\PresentationpageTemplates\PresentationpageTemplatesService;
use App\Service\Feature\Recordings\VideoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PresentationpagesController extends AbstractController
{
    public function showAction(
        string $presentationpageId,
        EntityManagerInterface $entityManager,
        VideoService $videoService
    ): Response {
        $presentationpage = $entityManager->find(Presentationpage::class, $presentationpageId);

        if (is_null($presentationpage)) {
            throw new NotFoundHttpException("No presentationpage with id '$presentationpageId'.");
        }

        return $this->render(
            'feature/presentationpages/show.html.twig',
            [
                'presentationpage' => $presentationpage,
                'VideoService' => $videoService
            ]
        );
    }

    public function createFromVideoAction(
        Request $request,
        EntityManagerInterface $entityManager,
        PresentationpageTemplatesService $presentationpageTemplatesService
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getPresentationpageTemplates()->count() === 0) {
            $presentationpageTemplate = $presentationpageTemplatesService->createDefaultTemplate($user);
        } else {
            $presentationpageTemplate = $user->getPresentationpageTemplates()->first();
        }

        $videoId = $request->get('videoId');

        $video = $entityManager->find(Video::class, $videoId);

        if (is_null($video)) {
            throw new NotFoundHttpException("A recording session full video with id '$videoId' does not exist.");
        }

        if ($user->getId() !== $video->getRecordingSession()->getUser()->getId()) {
            throw new AccessDeniedHttpException("The recording session full video with id '$videoId' does not belong to the current user.");
        }

        $presentationpage = new Presentationpage();
        $presentationpage->setUser($user);

        $presentationpage->setVideo($video);
        $presentationpage->setPresentationpageTemplate($presentationpageTemplate);

        $entityManager->persist($presentationpage);
        $entityManager->flush();

        return $this->redirectToRoute(
            'feature.presentationpages.editor',
            ['presentationpageId' => $presentationpage->getId()]
        );
    }


    public function editorAction(
        string $presentationpageId,
        EntityManagerInterface $entityManager,
        PresentationpageTemplatesService $presentationpageTemplatesService,
        VideoService $videoService
    ): Response {
        $presentationpage = $entityManager->find(Presentationpage::class, $presentationpageId);

        if (is_null($presentationpage)) {
            throw new NotFoundHttpException("No presentationpage with id '$presentationpageId'.");
        }

        /** @var User $user */
        $user = $this->getUser();

        if ($user->getId() !== $presentationpage->getUser()->getId()) {
            throw new AccessDeniedHttpException("The presentationpage with id '{$presentationpage->getId()}' does not belong to the current user.");
        }

        return $this->render(
            'feature/presentationpages/editor.html.twig',
            [
                'presentationpage' => $presentationpage,
                'PresentationpageTemplatesService' => $presentationpageTemplatesService,
                'VideoService' => $videoService
            ]
        );
    }


    public function switchToTemplateAction(
        string $presentationpageId,
        string $presentationpageTemplateId,
        EntityManagerInterface $entityManager
    ): Response {
        $presentationpage = $entityManager->find(Presentationpage::class, $presentationpageId);

        if (is_null($presentationpage)) {
            throw new NotFoundHttpException("No presentationpage with id '$presentationpageId'.");
        }

        $presentationpageTemplate = $entityManager->find(PresentationpageTemplate::class, $presentationpageTemplateId);

        if (is_null($presentationpageTemplate)) {
            throw new NotFoundHttpException("No presentationpage template with id '$presentationpageId'.");
        }

        /** @var User $user */
        $user = $this->getUser();

        if ($user->getId() !== $presentationpage->getUser()->getId()) {
            throw new AccessDeniedHttpException("The presentationpage with id '{$presentationpage->getId()}' does not belong to the current user.");
        }

        if ($user->getId() !== $presentationpageTemplate->getUser()->getId()) {
            throw new AccessDeniedHttpException("The presentationpage template with id '{$presentationpageTemplate->getId()}' does not belong to the current user.");
        }

        $presentationpage->setPresentationpageTemplate($presentationpageTemplate);
        $entityManager->persist($presentationpage);
        $entityManager->flush();

        return $this->redirectToRoute(
            'feature.presentationpages.editor',
            ['presentationpageId' => $presentationpage->getId()]
        );
    }
}