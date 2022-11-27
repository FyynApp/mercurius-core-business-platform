<?php

namespace App\VideoBasedMarketing\RecordingRequests\Domain\Service;

use App\Shared\Infrastructure\Service\ShortIdService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\RecordingRequests\Domain\Entity\RecordingRequest;
use App\VideoBasedMarketing\RecordingRequests\Domain\Entity\RecordingRequestResponse;
use App\VideoBasedMarketing\RecordingRequests\Domain\Enum\RecordingRequestResponseStatus;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class RecordingRequestsDomainService
{
    private EntityManagerInterface $entityManager;

    private ShortIdService $shortIdService;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ShortIdService         $shortIdService
    )
    {
        $this->entityManager = $entityManager;
        $this->shortIdService = $shortIdService;
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
    public function createRequest(
        User $user
    ): RecordingRequest
    {
        $recordingRequest = new RecordingRequest($user);
        $this->entityManager->persist($recordingRequest);
        $this->shortIdService->encodeObjectId($recordingRequest);

        $this->entityManager->flush();

        return $recordingRequest;
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

    public function userMustBeAskedToHandleResponsesAfterRecording(
        User $user
    ): bool
    {
        return sizeof(
            $this->getUnansweredRecordingRequestResponsesForUser($user)
            )
            > 0;
    }

    /** @return RecordingRequestResponse[] */
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
