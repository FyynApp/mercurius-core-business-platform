<?php

namespace App\Controller\Feature\Recordings;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Recordings\AssetMimeType;
use App\Entity\Feature\Recordings\RecordingSession;
use App\Service\Feature\Recordings\RecordingSessionService;
use App\Service\Feature\Recordings\VideoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

        // Edge case, if the user came here twice
        if ($recordingSession->isFinished()) {
            return $this->redirectToRoute('feature.recordings.videos.overview');
        }

        $recordingSessionService->handleRecordingSessionFinished($recordingSession, $videoService);

        return $this->redirectToRoute('feature.recordings.videos.overview');
    }

    public function videosOverviewAction(VideoService $videoService): Response
    {
        return $this->render(
            'feature/recordings/videos_overview.html.twig',
            ['VideoService' => $videoService]
        );
    }

    public function recordingPreviewAssetRedirectAction(
        string $recordingSessionId,
        Request $request,
        RecordingSessionService $recordingSessionService,
        EntityManagerInterface $entityManager,
        VideoService $videoService
    ): Response {

        $recordingSession = $entityManager->find(RecordingSession::class, $recordingSessionId);

        if (is_null($recordingSession)) {
            throw new NotFoundHttpException("Could not find recording session with id '$recordingSessionId'.");
        }

        if ($recordingSession->hasRecordingPreviewAssetBeenGenerated()) {
            return $this->redirectToRoute(
                'feature.recordings.recording_session.recording_preview.asset',
                [
                    'recordingSessionId' => $recordingSessionId,
                    'extension' => $videoService->mimeTypeToFileSuffix(AssetMimeType::VideoWebm),
                    'random' => random_bytes(8)
                ]
            );
        }

        $counter = $request->get('counter');

        if (is_null($counter)) {
            $counter = 6;
        }

        if ($counter > 0) {
            sleep(1);
            return $this->redirectToRoute(
                'feature.recordings.recording_session.recording_preview.asset-redirect',
                [
                    'counter' => $counter - 1,
                    'recordingSessionId' => $recordingSessionId,
                    'random' => random_bytes(8)
                ]
            );
        } else {
            $videoService->generateAssetFullWebm($recordingSession, $recordingSessionService->getRecordingPreviewVideoFilePath($recordingSession));
            $recordingSession->setRecordingPreviewAssetHasBeenGenerated(true);
            $entityManager->persist($recordingSession);
            $entityManager->flush();

            return $this->redirectToRoute(
                'feature.recordings.recording_session.recording_preview.asset',
                [
                    'recordingSessionId' => $recordingSessionId,
                    'extension' => $videoService->mimeTypeToFileSuffix(AssetMimeType::VideoWebm),
                    'random' => random_bytes(8)
                ]
            );
        }
    }
}
