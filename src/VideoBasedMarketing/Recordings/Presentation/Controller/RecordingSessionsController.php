<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Enum\VotingAttribute;
use App\VideoBasedMarketing\RecordingRequests\Domain\Service\RecordingRequestsDomainService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use App\VideoBasedMarketing\Recordings\Domain\Service\RecordingSessionDomainService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;


class RecordingSessionsController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/recording-sessions/{recordingSessionId}/edit-extension-recording',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/aufnahmesitzungen/{recordingSessionId}/aufnahme-in-browser-erweiterung-bearbeiten',
        ],
        name        : 'videobasedmarketing.recordings.presentation.recording_session.extension_edit',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function extensionRecordingSessionEditAction(
        string $recordingSessionId
    ): Response
    {
        $r = $this->verifyAndGetUserAndEntity(
            RecordingSession::class,
            $recordingSessionId,
            VotingAttribute::Use
        );

        /** @var RecordingSession $recordingSession */
        $recordingSession = $r->getEntity();

        if ($r->getUser()->isRegistered()) {
            throw new AccessDeniedHttpException(
                'The logged in user must be unregistered.'
            );
        }

        if (!$recordingSession->isFinished()) {
            throw $this->createNotFoundException(
                "Recording session '{$recordingSession->getId()}' is not finished."
            );
        }

        return $this->render(
            '@videobasedmarketing.recordings/videos_overview.html.twig',
            ['showEditModalForVideoId' => $recordingSession->getVideo()->getId()]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/recording-sessions/{recordingSessionId}/extension-recording-finished',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/aufnahmesitzungen/{recordingSessionId}/aufnahme-in-browser-erweiterung-abgeschlossen',
        ],
        name        : 'videobasedmarketing.recordings.presentation.recording_session.extension_finished',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function extensionRecordingSessionFinishedAction(
        string                         $recordingSessionId,
        Request                        $request,
        EntityManagerInterface         $entityManager,
        RecordingSessionDomainService  $recordingSessionDomainService,
        RecordingRequestsDomainService $recordingRequestsDomainService
    ): Response
    {
        /** @var ?User $user */
        $user = $this->getUser();

        if (is_null($user)) {
            throw new AccessDeniedHttpException('A logged-in registered or unregistered user is required.');
        }

        /** @var RecordingSession|null $recordingSession */
        $recordingSession = $entityManager
            ->find(RecordingSession::class, $recordingSessionId);

        if (is_null($recordingSession)) {
            throw $this->createNotFoundException(
                "No recording session found with id '$recordingSessionId'."
            );
        }

        $this->denyAccessUnlessGranted(
            VotingAttribute::Use->value,
            $recordingSession
        );

        $video = $recordingSessionDomainService
            ->handleRecordingSessionFinished(
                $recordingSession
            );

        if ($user->isRegistered()) {
            $routeName = 'videobasedmarketing.recordings.presentation.videos.overview';
            $routeParameters = ['showEditModalForVideoId' => $recordingSession->getVideo()->getId()];
        } else {
            if ($request->get('userWantsToEdit') === '1') {
                $routeName = 'videobasedmarketing.recordings.presentation.recording_session.extension_edit';
                $routeParameters = ['recordingSessionId' => $recordingSession->getId()];
            } else {
                $routeName = 'videobasedmarketing.account.presentation.claim_unregistered_user.landingpage';
                $routeParameters = [];
            }
        }

        if ($recordingRequestsDomainService
            ->userMustBeAskedToHandleResponsesAfterRecording(
                $user
            )
        ) {
            return $this->redirectToRoute(
                'videobasedmarketing.recording_requests.ask_to_handle_responses',
                [
                    'videoId' => $video->getId(),
                    'followUpRouteName' => $routeName,
                    'followUpRouteParameters' => json_encode($routeParameters)
                ]
            );
        } else {
            return $this->redirectToRoute(
                $routeName,
                $routeParameters
            );
        }
    }
}
