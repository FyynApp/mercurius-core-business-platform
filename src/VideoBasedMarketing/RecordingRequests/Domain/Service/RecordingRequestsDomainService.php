<?php

namespace App\VideoBasedMarketing\RecordingRequests\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\RecordingRequests\Domain\Entity\RecordingRequest;
use App\VideoBasedMarketing\RecordingRequests\Domain\Entity\RecordingRequestResponse;
use App\VideoBasedMarketing\RecordingRequests\Domain\Enum\RecordingRequestResponseStatus;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class RecordingRequestsDomainService
{
    private EntityManagerInterface $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        EntityManagerInterface $entityManager
    )
    {
        $this->entityManager = $entityManager;
    }

    public function getRecordingRequestForRespondingUser(
        User $user
    ): ?RecordingRequest
    {
        return null;
    }

    public function needToCreateResponse(
        RecordingRequest $recordingRequest,
        User             $user
    ): bool
    {
        foreach ($user->getRecordingRequestResponses() as $recordingRequestResponse) {
            if ($recordingRequestResponse->getRecordingRequest()->getId() === $recordingRequest->getId()) {
                if ($recordingRequestResponse->getStatus() === RecordingRequestResponseStatus::UNANSWERED) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public function createResponse(
        RecordingRequest $recordingRequest,
        User             $user
    ): RecordingRequestResponse
    {
        $response = new RecordingRequestResponse(
            $user,
            $recordingRequest
        );

        $user->addRecordingRequestResponse($response);
        $recordingRequest->addResponse($response);

        $this->entityManager->persist($response);
        $this->entityManager->persist($user);
        $this->entityManager->persist($recordingRequest);

        return $response;
    }

    public function getUnansweredRecordingRequestResponsesForUser(
        User $user
    ): array
    {
        $unansweredRecordingRequestResponses = [];

        foreach ($user->getRecordingRequestResponses() as $recordingRequestResponse) {
            if ($recordingRequestResponse->getStatus() === RecordingRequestResponseStatus::UNANSWERED) {
                $unansweredRecordingRequestResponses[] = $recordingRequestResponse;
            }
        }

        return $unansweredRecordingRequestResponses;
    }
}
