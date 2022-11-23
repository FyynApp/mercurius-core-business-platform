<?php

namespace App\VideoBasedMarketing\RecordingRequests\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Service\UserDomainService;
use App\VideoBasedMarketing\Account\Infrastructure\Service\RequestParametersBasedUserAuthService;
use App\VideoBasedMarketing\RecordingRequests\Domain\Entity\RecordingRequest;
use App\VideoBasedMarketing\RecordingRequests\Domain\Service\RecordingRequestsDomainService;
use Doctrine\ORM\EntityManagerInterface;
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
        name        : 'videobasedmarketing.recording_requests.create_recording_request',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function createRecordingRequestAction(

    ): Response
    {
        return new Response();
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
    public function respondToRecordingRequestAction(
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
