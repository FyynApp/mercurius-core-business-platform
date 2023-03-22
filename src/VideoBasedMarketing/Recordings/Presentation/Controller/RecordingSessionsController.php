<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Enum\AccessAttribute;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\RecordingRequests\Domain\Service\RecordingRequestsDomainService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use App\VideoBasedMarketing\Recordings\Domain\Service\RecordingSessionDomainService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;


class RecordingSessionsController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/recording-sessions/{recordingSessionId}/finished',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/aufnahmesitzungen/{recordingSessionId}/abgeschlossen',
        ],
        name        : 'videobasedmarketing.recordings.presentation.recording_session.finished',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function recordingSessionFinishedAction(
        string                         $recordingSessionId,
        EntityManagerInterface         $entityManager,
        RecordingSessionDomainService  $recordingSessionDomainService,
        RecordingRequestsDomainService $recordingRequestsDomainService,
        CapabilitiesService            $capabilitiesService
    ): Response
    {
        /** @var null|User $user */
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
            AccessAttribute::Use->value,
            $recordingSession
        );

        $video = $recordingSessionDomainService
            ->handleRecordingSessionFinished(
                $recordingSession
            );

        $routeParameters = [];
        if ($capabilitiesService->mustBeForcedToClaimUnregisteredUser($user)) {
            $routeName = 'videobasedmarketing.account.presentation.claim_unregistered_user.landingpage';
        } else {
            $routeName = 'videobasedmarketing.recordings.presentation.videos.overview';
        }

        if ($recordingRequestsDomainService
            ->userMustBeAskedToHandleResponsesAfterRecording(
                $user
            )
        ) {
            return $this->redirectToRoute(
                'videobasedmarketing.recording_requests.ask_to_handle_responses',
                ['videoId' => $video->getId()]
            );
        } else {
            return $this->redirectToRoute(
                $routeName,
                $routeParameters
            );
        }
    }


    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/rs/{recordingSessionShortId}',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/rs/{recordingSessionShortId}',
        ],
        name        : 'videobasedmarketing.recordings.presentation.recording_session.share',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function recordingSessionShareAction(
        string                         $recordingSessionShortId,
        EntityManagerInterface         $entityManager,
        RecordingSessionDomainService  $recordingSessionDomainService
    ): Response
    {
        /** @var EntityRepository $r */
        $r = $entityManager->getRepository(RecordingSession::class);

        /** @var null|RecordingSession $recordingSession */
        $recordingSession = $r->findOneBy(['shortId' => $recordingSessionShortId]);

        if (is_null($recordingSession)) {
            throw $this->createNotFoundException("No recording session with short id '$recordingSessionShortId' found.");
        }

        if (   !is_null($recordingSession->getVideo())
            && !is_null($recordingSession->getVideo()->getShortId())
        ) {
            return $this->redirectToRoute(
                'videobasedmarketing.recordings.presentation.video.share_link',
                ['videoShortId' => $recordingSession->getVideo()->getShortId()]
            );
        }

        $video = $recordingSessionDomainService
            ->handleRecordingSessionFinished(
                $recordingSession
            );

        return $this->redirectToRoute(
            'videobasedmarketing.recordings.presentation.video.share_link',
            ['videoShortId' => $video->getShortId()]
        );
    }
}
