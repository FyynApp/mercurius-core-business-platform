<?php

namespace App\VideoBasedMarketing\Recordings\Presentation\Controller;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Enum\VotingAttribute;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use App\VideoBasedMarketing\Recordings\Domain\Service\RecordingSessionService;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoService;
use App\VideoBasedMarketing\Recordings\Infrastructure\Enum\AssetMimeType;
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
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/recording-sessions/{recordingSessionId}/extension-recording-finished',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/aufnahmesitzungen/{recordingSessionId}/aufnahme-in-browser-erweiterung-abgeschlossen',
        ],
        name        : 'videobasedmarketing.recordings.presentation.recording_session.finished',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function extensionRecordingFinishedAction(
        string $recordingSessionId,
        RecordingSessionService $recordingSessionService,
        EntityManagerInterface $entityManager
    ): Response
    {
        /** @var ?User $user */
        $user = $this->getUser();

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

        return new Response(Response::HTTP_NOT_IMPLEMENTED);
    }
}
