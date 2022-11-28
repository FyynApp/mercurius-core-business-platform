<?php

namespace App\VideoBasedMarketing\RecordingRequests\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Enum\VotingAttribute;
use App\VideoBasedMarketing\Account\Domain\Service\UserDomainService;
use App\VideoBasedMarketing\Account\Infrastructure\Service\RequestParametersBasedUserAuthService;
use App\VideoBasedMarketing\RecordingRequests\Domain\Entity\RecordingRequest;
use App\VideoBasedMarketing\RecordingRequests\Domain\Entity\RecordingRequestResponse;
use App\VideoBasedMarketing\RecordingRequests\Domain\Service\RecordingRequestsDomainService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RecordingRequestsController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/recording-requests/',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/aufnahme-anfragen/',
        ],
        name        : 'videobasedmarketing.recording_requests.recording_requests_overview',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function recordingRequestsOverviewAction(): Response
    {
        $user = $this->getUser(true);

        return $this->render(
            '@videobasedmarketing.recording_requests/recording_requests_overview.html.twig',
            [
                'recordingRequests' => $user->getRecordingRequests()
            ]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/recording-requests/',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/aufnahme-anfragen/',
        ],
        name        : 'videobasedmarketing.recording_requests.create_recording_request',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function createRecordingRequestAction(
        RecordingRequestsDomainService $recordingRequestsDomainService
    ): Response
    {
        $user = $this->getUser(true);

        $recordingRequest = $recordingRequestsDomainService->createRequest($user);

        return $this->redirectToRoute(
            'videobasedmarketing.recording_requests.recording_request_share',
            [
                'recordingRequestShortId' => $recordingRequest->getShortId()
            ]
        );
    }

    #[Route(
        path        : '/rr/{recordingRequestShortId}',
        name        : 'videobasedmarketing.recording_requests.recording_request_share',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function recordingRequestShareAction(
        string                 $recordingRequestShortId,
        EntityManagerInterface $entityManager
    ): Response
    {
        /** @var EntityRepository $r */
        $r = $entityManager->getRepository(RecordingRequest::class);

        /** @var null|RecordingRequest $recordingRequest */
        $recordingRequest = $r->findOneBy(['shortId' => $recordingRequestShortId]);

        if (is_null($recordingRequest)) {
            throw $this->createNotFoundException("No recording request with short id '$recordingRequestShortId' found.");
        }

        $user = $this->getUser();
        if (!is_null($user) && $user->getId() === $recordingRequest->getUser()->getId()) {
            return $this->render(
                '@videobasedmarketing.recording_requests/recording_request_owner_info.html.twig',
                ['recordingRequest' => $recordingRequest]
            );
        }

        return $this->redirectToRoute(
            'videobasedmarketing.recording_requests.show_response_instructions',
            ['recordingRequestId' => $recordingRequest->getId()]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/recording-requests/{recordingRequestId}/response-instructions',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/aufnahme-anfragen/{recordingRequestId}/antwort-anleitung',
        ],
        name        : 'videobasedmarketing.recording_requests.show_response_instructions',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET, Request::METHOD_POST]
    )]
    public function showRecordingRequestResponseInstructionsAction(
        string                                $recordingRequestId,
        Request                               $request,
        EntityManagerInterface                $entityManager,
        UserDomainService                     $userDomainService,
        RequestParametersBasedUserAuthService $requestParametersBasedUserAuthService,
        RecordingRequestsDomainService        $recordingRequestsDomainService
    ): Response
    {
        $user = $this->getUser();

        if (is_null($user)) {
            $user = $userDomainService->createUnregisteredUser();
            return $requestParametersBasedUserAuthService->createRedirectResponse(
                $user,
                'videobasedmarketing.recording_requests.show_response_instructions',
                ['recordingRequestId' => $recordingRequestId]
            );
        }

        $recordingRequest = $entityManager->find(RecordingRequest::class, $recordingRequestId);

        if (is_null($recordingRequest)) {
            throw $this->createNotFoundException(
                "Recording request with id '$recordingRequestId' not found."
            );
        }

        if (   $request->getMethod() === Request::METHOD_POST
            && $recordingRequestsDomainService
                ->needToCreateResponse(
                    $recordingRequest,
                    $user
                )
        ) {
            $recordingRequestsDomainService->createResponse($recordingRequest, $user);
            return $this->redirectToRoute(
                'videobasedmarketing.recording_requests.show_response_instructions',
                ['recordingRequestId' => $recordingRequestId]
            );
        }

        return $this->render(
            '@videobasedmarketing.recording_requests/response_instructions.html.twig',
            [
                'recordingRequest' => $recordingRequest,

                'needToCreateResponse' => $recordingRequestsDomainService
                    ->needToCreateResponse(
                        $recordingRequest,
                        $user
                    )
            ]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/recording-requests/handle-responses/with-video/{videoId}',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/aufnahme-anfragen/anfrage-antworten-behandeln/mit-aufnahme/{videoId}',
        ],
        name        : 'videobasedmarketing.recording_requests.ask_to_handle_responses',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function askToHandleResponsesAction(
        string                         $videoId,
        Request                        $request,
        RecordingRequestsDomainService $recordingRequestsDomainService
    ): Response
    {
        $followUpRouteName = $request->get('followUpRouteName');
        $followUpRouteParameters = $request->get('followUpRouteParameters');

        $result = $this->verifyAndGetUserAndEntity(
            Video::class,
            $videoId,
            VotingAttribute::Use
        );

        $responsesThatNeedToBeAnswered = $recordingRequestsDomainService
            ->getResponsesThatNeedToBeAnsweredByUser(
                $result->getUser()
            );

        if (sizeof($responsesThatNeedToBeAnswered) === 0) {
            return $this->redirectToRoute(
                $followUpRouteName,
                json_decode($followUpRouteParameters, true)
            );
        }

        return $this->render(
            '@videobasedmarketing.recording_requests/ask_to_handle_responses.html.twig',
            [
                'video' => $result->getEntity(),
                'responses' => $responsesThatNeedToBeAnswered,
                'followUpRouteName' => $followUpRouteName,
                'followUpRouteParameters' => $followUpRouteParameters
            ]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/recording-requests/responses/{recordingRequestResponseId}/attach-to-video/{videoId}',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/aufnahme-anfragen/antworten/{recordingRequestResponseId}/verbinden-mit-aufnahme/{videoId}',
        ],
        name        : 'videobasedmarketing.recording_requests.answer_response_with_video',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function answerResponseWithVideoAction(
        string                         $videoId,
        string                         $recordingRequestResponseId,
        Request                        $request,
        RecordingRequestsDomainService $recordingRequestsDomainService
    ): Response
    {
        $result = $this->verifyAndGetUserAndEntity(
            Video::class,
            $videoId,
            VotingAttribute::Use
        );

        /** @var Video $video */
        $video = $result->getEntity();

        $result = $this->verifyAndGetUserAndEntity(
            RecordingRequestResponse::class,
            $recordingRequestResponseId,
            VotingAttribute::Use
        );

        /** @var RecordingRequestResponse $recordingRequestResponse */
        $recordingRequestResponse = $result->getEntity();

        $recordingRequestsDomainService->answerResponseWithVideo(
            $recordingRequestResponse,
            $video
        );

        return $this->redirectToRoute(
            $request->get('followUpRouteName'),
            json_decode($request->get('followUpRouteParameters'), true)
        );
    }
}
