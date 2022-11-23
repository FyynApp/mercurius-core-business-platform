<?php

namespace App\VideoBasedMarketing\RecordingRequests\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Service\UserDomainService;
use App\VideoBasedMarketing\Account\Infrastructure\Service\RequestParametersBasedUserAuthService;
use App\VideoBasedMarketing\RecordingRequests\Domain\Entity\RecordingRequest;
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
    public function recordingRequestsOverviewAction(
    ): Response
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

        return $this->render(
            '@videobasedmarketing.recording_requests/recording_request_created.html.twig',
            ['recordingRequest' => $recordingRequest]
        );
    }

    #[Route(
        path        : '/rr/{videoShortId}',
        name        : 'videobasedmarketing.recording_requests.recording_request_share',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function recordingRequestShareAction(
        string                 $shortId,
        EntityManagerInterface $entityManager
    ): Response
    {
        /** @var EntityRepository $r */
        $r = $entityManager->getRepository(RecordingRequest::class);

        /** @var null|RecordingRequest $recordingRequest */
        $recordingRequest = $r->findOneBy(['shortId' => $shortId]);

        if (is_null($recordingRequest)) {
            throw $this->createNotFoundException("No recording request with short id '$shortId' found.");
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
        methods     : [Request::METHOD_GET]
    )]
    public function showRecordingRequestResponseInstructionsAction(
        string                                $recordingRequestId,
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

        return $this->render(
            '@videobasedmarketing.recording_requests/response_instructions.html.twig',
            [
                'recordingRequest' => $recordingRequest,

                'isViewedByRequester' =>
                    $recordingRequest->getUser()->getId() === $this->getUser()->getId(),

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
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/recording-requests/please-handle-responses/with-video/{videoId}',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/aufnahme-anfragen/bitte-anfrage-antworten-behandeln/mit-aufnahme/{videoId}',
        ],
        name        : 'videobasedmarketing.recording_requests.ask_to_handle_responses',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function askToHandleRequestsAction(
        string                                $recordingRequestId,
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

        return $this->render(
            '@videobasedmarketing.recording_requests/response_instructions.html.twig',
            [
                'recordingRequest' => $recordingRequest,

                'isViewedByRequester' =>
                    $recordingRequest->getUser()->getId() === $this->getUser()->getId(),

                'needToCreateResponse' => $recordingRequestsDomainService
                    ->needToCreateResponse(
                        $recordingRequest,
                        $user
                    )
            ]
        );
    }
}
