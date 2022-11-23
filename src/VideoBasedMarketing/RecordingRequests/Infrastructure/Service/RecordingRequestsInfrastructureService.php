<?php

namespace App\VideoBasedMarketing\RecordingRequests\Infrastructure\Service;


use App\VideoBasedMarketing\RecordingRequests\Domain\Entity\RecordingRequest;
use Symfony\Component\Routing\RouterInterface;

class RecordingRequestsInfrastructureService
{
    public function getRecordingRequestShareUrl(
        RecordingRequest $recordingRequest,
        RouterInterface  $router
    ): string
    {
        return $router->generate(
            'videobasedmarketing.recording_requests.recording_request_share',
            [
                'recordingRequestId' => $recordingRequest->getId()
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}
