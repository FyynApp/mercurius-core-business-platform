<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Controller;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Enum\VotingAttribute;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use App\VideoBasedMarketing\Recordings\Domain\Service\RecordingSessionDomainService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
        string $recordingSessionId,
        EntityManagerInterface $entityManager,
        RecordingSessionDomainService $recordingSessionDomainService
    ): Response
    {
        return new Response('', Response::HTTP_NOT_IMPLEMENTED);
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
        string $recordingSessionId,
        EntityManagerInterface $entityManager,
        RecordingSessionDomainService $recordingSessionDomainService
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
            throw new NotFoundHttpException(
                "No recording session found with id '$recordingSessionId'."
            );
        }

        $this->denyAccessUnlessGranted(
            VotingAttribute::Use->value,
            $recordingSession
        );

        if ($user->isRegistered()) {
            return $this->redirectToRoute(
                'videobasedmarketing.recordings.presentation.return_from_recording_studio',
                ['recordingSessionId' => $recordingSession->getId()]
            );
        }

        if (!$recordingSession->isFinished()) {
            $recordingSessionDomainService
                ->handleRecordingSessionFinished(
                    $recordingSession
                );
        }

        return $this->redirectToRoute(
            'videobasedmarketing.account.presentation.claim_unregistered_user_landinpage'
        );
    }
}
