<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Enum\VotingAttribute;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use App\VideoBasedMarketing\Recordings\Domain\Service\RecordingSessionDomainService;
use App\VideoBasedMarketing\Recordings\Infrastructure\Enum\AssetMimeType;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;


class RecordingsController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/recordings/recording-studio',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/aufnahmen/aufnahmestudio',
        ],
        name        : 'videobasedmarketing.recordings.presentation.recording_studio',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function recordingStudioAction(
        RecordingSessionDomainService $recordingSessionDomainService
    ): Response
    {
        /** @var ?User $user */
        $user = $this->getUser();

        if (is_null($user)) {
            throw new AccessDeniedHttpException();
        }

        $recordingSession = $recordingSessionDomainService
            ->startRecordingSession($user);

        return $this->render(
            '@videobasedmarketing.recordings/recording_studio.html.twig',
            ['recordingSession' => $recordingSession]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/recordings/recording-studio/return',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/aufnahmen/aufnahmestudio/rückkehr',
        ],
        name        : 'videobasedmarketing.recordings.presentation.return_from_recording_studio',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function returnFromRecordingStudioAction(
        Request                       $request,
        EntityManagerInterface        $entityManager,
        RecordingSessionDomainService $recordingSessionDomainService
    ): Response
    {
        $recordingSessionId = $request->get('recordingSessionId');

        $recordingSession = $entityManager->find(RecordingSession::class, $recordingSessionId);

        if (is_null($recordingSession)) {
            throw $this->createNotFoundException("No recording session with id '$recordingSessionId'.");
        }

        $this->denyAccessUnlessGranted(VotingAttribute::Use->value, $recordingSession);

        $video = $recordingSessionDomainService
            ->handleRecordingSessionFinished(
                $recordingSession
            );

        return $this->redirectToRoute(
            'videobasedmarketing.recordings.presentation.videos.overview',
            ['showEditModalForVideoId' => $video->getId()]
        );
    }

    #[Route(
        path   : 'recordings/recording-sessions/{recordingSessionId}/recording-preview-asset-redirect',
        name   : 'videobasedmarketing.recordings.presentation.recording_session.recording_preview.asset_redirect',
        methods: [Request::METHOD_GET]
    )]
    public function recordingPreviewAssetRedirectAction(
        string                          $recordingSessionId,
        Request                         $request,
        RecordingsInfrastructureService $recordingsInfrastructureService
    ): Response
    {
        $r = $this->verifyAndGetUserAndEntity(
            RecordingSession::class,
            $recordingSessionId,
            VotingAttribute::Use
        );

        /** @var RecordingSession $recordingSession */
        $recordingSession = $r->getEntity();

        if ($recordingSession->hasRecordingPreviewAssetBeenGenerated()) {
            return $this->redirectToRoute(
                'videobasedmarketing.recordings.presentation.recording_session.recording_preview.asset',
                [
                    'recordingSessionId' => $recordingSessionId,
                    'extension' => $recordingsInfrastructureService->mimeTypeToFileSuffix(AssetMimeType::VideoWebm),
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
                'videobasedmarketing.recordings.presentation.recording_session.recording_preview.asset_redirect',
                [
                    'counter' => $counter - 1,
                    'recordingSessionId' => $recordingSessionId,
                    'random' => bin2hex(random_bytes(8))
                ]
            );
        } else {
            $recordingsInfrastructureService
                ->generateRecordingPreviewVideo($recordingSession);

            return $this->redirectToRoute(
                'videobasedmarketing.recordings.presentation.recording_session.recording_preview.asset',
                [
                    'recordingSessionId' => $recordingSessionId,
                    'extension' => $recordingsInfrastructureService->mimeTypeToFileSuffix(AssetMimeType::VideoWebm),
                    'random' => bin2hex(random_bytes(8))
                ]
            );
        }
    }
}
