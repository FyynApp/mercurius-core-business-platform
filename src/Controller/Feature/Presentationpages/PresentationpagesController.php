<?php

namespace App\Controller\Feature\Presentationpages;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Presentationpages\Presentationpage;
use App\Entity\Feature\PresentationpageTemplates\PresentationpageTemplate;
use App\Entity\Feature\Recordings\RecordingSessionFullVideo;
use App\Service\Feature\PresentationpageTemplates\PresentationpageTemplatesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PresentationpagesController extends AbstractController
{
    public function showAction(string $presentationpageId, EntityManagerInterface $entityManager): Response
    {
        $presentationpage = $entityManager->find(Presentationpage::class, $presentationpageId);

        if (is_null($presentationpage)) {
            throw new NotFoundHttpException("No presentationpage with id '$presentationpageId'.");
        }

        return $this->render(
            'feature/presentationpages/show.html.twig',
            ['presentationpage' => $presentationpage]
        );
    }

    public function createFromRecordingSessionFullVideoAction(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $recordingSessionFullVideoId = $request->get('recordingSessionFullVideoId');

        $recordingSessionFullVideo = $entityManager->find(RecordingSessionFullVideo::class, $recordingSessionFullVideoId);

        if (is_null($recordingSessionFullVideo)) {
            throw new NotFoundHttpException("A recording session full video with id '$recordingSessionFullVideoId' does not exist.");
        }

        if ($user->getId() !== $recordingSessionFullVideo->getRecordingSession()->getUser()->getId()) {
            throw new AccessDeniedHttpException("The recording session full video with id '$recordingSessionFullVideoId' does not belong to the current user.");
        }

        $presentationpage = new Presentationpage();
        $presentationpage->setUser($user);

        $presentationpage->setRecordingSessionFullVideo($recordingSessionFullVideo);
        $presentationpage->setPresentationpageTemplate($user->getPresentationpageTemplates()->first());

        $entityManager->persist($presentationpage);
        $entityManager->flush();

        return $this->redirectToRoute(
            'feature.presentationpages.editor',
            ['presentationpageId' => $presentationpage->getId()]
        );
    }


    public function editorAction(string $presentationpageId, EntityManagerInterface $entityManager, PresentationpageTemplatesService $presentationpageTemplatesService): Response
    {
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
                'PresentationpageTemplatesService' => $presentationpageTemplatesService
            ]
        );
    }


    public function switchToTemplateAction(string $presentationpageId, string $presentationpageTemplateId, EntityManagerInterface $entityManager): Response
    {
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