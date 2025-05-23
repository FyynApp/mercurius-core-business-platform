<?php

namespace App\VideoBasedMarketing\RecordingRequests\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\Shared\Presentation\Enum\FlashMessageLabel;
use App\VideoBasedMarketing\Account\Domain\Enum\AccessAttribute;
use App\VideoBasedMarketing\Account\Domain\Service\AccountDomainService;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\Account\Infrastructure\Service\RequestParametersBasedUserAuthService;
use App\VideoBasedMarketing\RecordingRequests\Domain\Entity\RecordingRequest;
use App\VideoBasedMarketing\RecordingRequests\Domain\Entity\RecordingRequestResponse;
use App\VideoBasedMarketing\RecordingRequests\Domain\Service\RecordingRequestsDomainService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoDomainService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

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
        RecordingRequestsDomainService $recordingRequestsDomainService
    ): Response
    {
        $user = $this->getUser(true);

        return $this->render(
            '@videobasedmarketing.recording_requests/recording_requests_overview.html.twig',
            [
                'recordingRequests' => $recordingRequestsDomainService
                    ->getAvailableRecordingRequestsForCurrentlyActiveOrganization($user)
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
        Request                        $request,
        RecordingRequestsDomainService $recordingRequestsDomainService
    ): Response
    {
        $user = $this->getUser(true);

        $requestVideoId = $request->get('requestVideoId');

        $requestVideo = null;
        if (!is_null($requestVideoId)) {
            $r = $this->verifyAndGetUserAndEntity(
                Video::class,
                $requestVideoId,
                AccessAttribute::Use
            );
            /** @var Video $requestVideo */
            $requestVideo = $r->getEntity();
        }

        $title = (string)$request->get('title');

        $recordingRequest = $recordingRequestsDomainService
            ->createRequest(
                $user,
                $title,
                $requestVideo
            );

        return $this->redirectToRoute(
            'videobasedmarketing.recording_requests.recording_request.show',
            [
                'recordingRequestId' => $recordingRequest->getId()
            ]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/recording-requests/{recordingRequestId}',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/aufnahme-anfragen/{recordingRequestId}',
        ],
        name        : 'videobasedmarketing.recording_requests.update_recording_request',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function updateRecordingRequestAction(
        string                 $recordingRequestId,
        Request                $request,
        TranslatorInterface    $translator,
        EntityManagerInterface $entityManager
    ): Response
    {
        if (!$this->isCsrfTokenValid('update-recording-request', $request->get('_csrf_token'))) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        $r = $this->verifyAndGetUserAndEntity(
            RecordingRequest::class,
            $recordingRequestId,
            AccessAttribute::Edit
        );
        /** @var RecordingRequest $recordingRequest */
        $recordingRequest = $r->getEntity();

        $recordingRequest->setTitle($request->get('title'));
        $recordingRequest->setRequestText($request->get('requestText'));

        $entityManager->persist($recordingRequest);
        $entityManager->flush();

        $this->addFlash(
            FlashMessageLabel::Success->value,
            $translator->trans(
                'recording_request_owner_info.flash_message_saved_success',
                [],
                'videobasedmarketing.recording_requests'
            )
        );

        return $this->redirectToRoute(
            'videobasedmarketing.recording_requests.recording_request.show',
            [
                'recordingRequestId' => $recordingRequest->getId()
            ]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/recording-requests/{recordingRequestId}',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/aufnahme-anfragen/{recordingRequestId}',
        ],
        name        : 'videobasedmarketing.recording_requests.recording_request.show',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function recordingRequestShowAction(
        string $recordingRequestId
    ): Response
    {
        $result = $this->verifyAndGetUserAndEntity(
            RecordingRequest::class,
            $recordingRequestId,
            AccessAttribute::Use
        );

        return $this->render(
            '@videobasedmarketing.recording_requests/recording_request_show.html.twig',
            ['recordingRequest' => $result->getEntity()]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/rr/{recordingRequestShortId}',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.en%/rr/{recordingRequestShortId}',
        ],
        name        : 'videobasedmarketing.recording_requests.recording_request_share',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function recordingRequestShareAction(
        string                 $recordingRequestShortId,
        EntityManagerInterface $entityManager
    ): Response
    {
        /** @var ObjectRepository<RecordingRequest> $r */
        $r = $entityManager->getRepository(RecordingRequest::class);

        /** @var null|RecordingRequest $recordingRequest */
        $recordingRequest = $r->findOneBy(['shortId' => $recordingRequestShortId]);

        if (is_null($recordingRequest)) {
            throw $this->createNotFoundException("No recording request with short id '$recordingRequestShortId' found.");
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
        AccountDomainService                  $accountDomainService,
        RequestParametersBasedUserAuthService $requestParametersBasedUserAuthService,
        RecordingRequestsDomainService        $recordingRequestsDomainService
    ): Response
    {
        $user = $this->getUser();

        if (is_null($user)) {
            $user = $accountDomainService->createUnregisteredUser();
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
        RecordingRequestsDomainService $recordingRequestsDomainService,
        CapabilitiesService            $capabilitiesService
    ): Response
    {
        $result = $this->verifyAndGetUserAndEntity(
            Video::class,
            $videoId,
            AccessAttribute::Use
        );

        $responsesThatNeedToBeAnswered = $recordingRequestsDomainService
            ->getResponsesThatNeedToBeAnsweredByUser(
                $result->getUser()
            );

        if (sizeof($responsesThatNeedToBeAnswered) === 0) {
            if ($capabilitiesService->mustBeForcedToClaimUnregisteredUser($this->getUser())) {
                return $this->redirectToRoute('videobasedmarketing.account.presentation.claim_unregistered_user.landingpage');
            } else {
                return $this->redirectToRoute('videobasedmarketing.recordings.presentation.videos.overview');
            }
        }

        return $this->render(
            '@videobasedmarketing.recording_requests/ask_to_handle_responses.html.twig',
            [
                'video' => $result->getEntity(),
                'responses' => $responsesThatNeedToBeAnswered
            ]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/recording-requests/{recordingRequestId}/handle-uploaded-video',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/aufnahme-anfragen/{recordingRequestId}/hochgeladene-aufnahme-behandeln',
        ],
        name        : 'videobasedmarketing.recording_requests.handle_uploaded_response_video',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function answerResponseWithUploadedVideoAction(
        string                 $recordingRequestId,
        EntityManagerInterface $entityManager,
        VideoDomainService     $videoDomainService
    ): Response
    {
        $user = $this->getUser(true);

        $video = $videoDomainService->getNewestUploadedVideoForUser($user);

        $recordingRequest = $entityManager->find(RecordingRequest::class, $recordingRequestId);

        if (is_null($recordingRequest)) {
            throw $this->createNotFoundException(
                "Recording request with id '$recordingRequestId' not found."
            );
        }

        if (!is_null($video)) {
            if (!$videoDomainService->videoIsCurrentlyBeingProcessed($video)) {
                return $this->redirectToRoute(
                    'videobasedmarketing.recording_requests.ask_to_handle_responses',
                    ['videoId' => $video->getId()]
                );
            }
        }

        return $this->render(
            '@videobasedmarketing.recording_requests/handle_uploaded_response_video.html.twig',
            [],
            new Response(
                null,
                Response::HTTP_OK,
                ['Refresh' => '5']
            )
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
        RecordingRequestsDomainService $recordingRequestsDomainService
    ): Response
    {
        $result = $this->verifyAndGetUserAndEntity(
            Video::class,
            $videoId,
            AccessAttribute::Use
        );

        /** @var Video $video */
        $video = $result->getEntity();

        $result = $this->verifyAndGetUserAndEntity(
            RecordingRequestResponse::class,
            $recordingRequestResponseId,
            AccessAttribute::Use
        );

        /** @var RecordingRequestResponse $recordingRequestResponse */
        $recordingRequestResponse = $result->getEntity();

        $recordingRequestsDomainService->answerResponseWithVideo(
            $recordingRequestResponse,
            $video
        );

        return $this->redirectToRoute(
            'videobasedmarketing.recording_requests.thank_you',
            ['recordingRequestResponseId' => $recordingRequestResponse->getId()]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/recording-requests/responses/{recordingRequestResponseId}/thank-you',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/aufnahme-anfragen/antworten/{recordingRequestResponseId}/danke',
        ],
        name        : 'videobasedmarketing.recording_requests.thank_you',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function thankYouAction(): Response
    {
        return $this->render('@videobasedmarketing.recording_requests/thank_you.html.twig');
    }
}
