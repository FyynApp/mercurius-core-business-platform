<?php

namespace App\Controller\Feature\Recordings;

use App\Entity\Feature\Recordings\AssetMimeType;
use App\Entity\Feature\Recordings\RecordingSession;
use App\Service\Feature\Recordings\RecordingSessionService;
use App\Service\Feature\Recordings\VideoService;
use App\VideoBasedMarketing\Account\Domain\Enum\VotingAttribute;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class RecordingsController
    extends AbstractController
{
    public function recordingStudioAction(
        RecordingSessionService $recordingSessionService
    ): Response
    {
        /** @var ?\App\VideoBasedMarketing\Account\Domain\Entity\User $user */
        $user = $this->getUser();

        if (is_null($user)) {
            throw new AccessDeniedHttpException();
        }

        $recordingSession = $recordingSessionService->startRecordingSession($user);

        return $this->render(
            'feature/recordings/recording_studio.html.twig',
            ['recordingSession' => $recordingSession]
        );
    }

    public function returnFromRecordingStudioAction(
        Request                 $request,
        EntityManagerInterface  $entityManager,
        RecordingSessionService $recordingSessionService,
        VideoService            $videoService
    ): Response
    {
        $recordingSessionId = $request->get('recordingSessionId');

        $recordingSession = $entityManager->find(RecordingSession::class, $recordingSessionId);

        if (is_null($recordingSession)) {
            throw new NotFoundHttpException("No recording session with id '$recordingSessionId'.");
        }

        $this->denyAccessUnlessGranted(VotingAttribute::Use->value, $recordingSession);

        // Edge case, if the user came here twice
        if (!$recordingSession->isFinished()) {
            $video = $recordingSessionService->handleRecordingSessionFinished($recordingSession, $videoService);
        } else {
            $video = $recordingSession->getVideo();
        }

        return $this->redirectToRoute(
            'feature.recordings.videos.overview',
            ['showEditModalForVideoId' => $video->getId()]
        );
    }

    public function videosOverviewAction(VideoService $videoService): Response
    {
        return $this->render(
            'feature/recordings/videos_overview.html.twig'
        );
    }

    public function recordingPreviewAssetRedirectAction(
        string                  $recordingSessionId,
        Request                 $request,
        RecordingSessionService $recordingSessionService,
        EntityManagerInterface  $entityManager,
        VideoService            $videoService
    ): Response
    {

        $recordingSession = $entityManager->find(RecordingSession::class, $recordingSessionId);

        if (is_null($recordingSession)) {
            throw new NotFoundHttpException("Could not find recording session with id '$recordingSessionId'.");
        }

        $this->denyAccessUnlessGranted(VotingAttribute::Use->value, $recordingSession);

        if ($recordingSession->hasRecordingPreviewAssetBeenGenerated()) {
            return $this->redirectToRoute(
                'feature.recordings.recording_session.recording_preview.asset',
                [
                    'recordingSessionId' => $recordingSessionId,
                    'extension' => $videoService->mimeTypeToFileSuffix(AssetMimeType::VideoWebm),
                    'random' => bin2hex(random_bytes(8))
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
                    'random' => bin2hex(random_bytes(8))
                ]
            );
        } else {
            $recordingSessionService->generateRecordingPreviewVideo($recordingSession);

            return $this->redirectToRoute(
                'feature.recordings.recording_session.recording_preview.asset',
                [
                    'recordingSessionId' => $recordingSessionId,
                    'extension' => $videoService->mimeTypeToFileSuffix(AssetMimeType::VideoWebm),
                    'random' => bin2hex(random_bytes(8))
                ]
            );
        }
    }
}
