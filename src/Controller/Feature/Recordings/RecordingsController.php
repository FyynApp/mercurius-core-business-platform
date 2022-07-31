<?php

namespace App\Controller\Feature\Recordings;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Recordings\RecordingSession;
use App\Service\Feature\Recordings\RecordingSessionService;
use App\Service\Feature\Recordings\RecordingsService;
use App\Service\Feature\Recordings\VideoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RecordingsController extends AbstractController
{
    public function recordingStudioAction(EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $recordingSession = new RecordingSession();
        $recordingSession->setUser($user);
        $entityManager->persist($recordingSession);
        $entityManager->flush($recordingSession);

        return $this->render(
            'feature/recordings/recording_studio.html.twig',
            ['recordingSession' => $recordingSession]
        );
    }

    public function returnFromRecordingStudioAction(
        Request $request,
        EntityManagerInterface $entityManager,
        RecordingSessionService $recordingSessionService,
        VideoService $videoService
    ): Response {
        $recordingSessionId = $request->get('recordingSessionId');

        $recordingSession = $entityManager->find(RecordingSession::class, $recordingSessionId);

        if (is_null($recordingSession)) {
            throw new NotFoundHttpException("No recording session with id '$recordingSessionId'.");
        }

        $recordingSessionService->handleRecordingSessionFinished($recordingSession, $videoService);

        return $this->redirectToRoute('feature.recordings.recording_sessions.overview');
    }

    public function recordingSessionsOverviewAction(RecordingsService $recordingsService): Response
    {
        return $this->render(
            'feature/recordings/recording_sessions_overview.html.twig',
            ['RecordingsService' => $recordingsService]
        );
    }
}
