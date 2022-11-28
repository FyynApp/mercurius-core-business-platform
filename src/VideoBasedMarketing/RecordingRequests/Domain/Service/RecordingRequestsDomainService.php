<?php

namespace App\VideoBasedMarketing\RecordingRequests\Domain\Service;

use App\Shared\Infrastructure\Service\ShortIdService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\RecordingRequests\Domain\Entity\RecordingRequest;
use App\VideoBasedMarketing\RecordingRequests\Domain\Entity\RecordingRequestResponse;
use App\VideoBasedMarketing\RecordingRequests\Domain\Enum\RecordingRequestResponseStatus;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
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

        $this->entityManager->flush();

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

    public function allResponsesAreAnswered(
        RecordingRequest $recordingRequest
    ): bool
    {
        if (sizeof($recordingRequest->getRecordingRequestResponses()) === 0) {
            return false;
        }

        foreach ($recordingRequest->getRecordingRequestResponses() as $recordingRequestResponse) {
            if (    $recordingRequestResponse->getStatus()
                === RecordingRequestResponseStatus::UNANSWERED
            ) {
                return false;
            }
        }

        return true;
    }

    public function getNumberOfUnansweredResponses(
        RecordingRequest $recordingRequest
    ): int
    {
        return sizeof($this->getUnansweredResponses($recordingRequest));
    }

    public function getUnansweredResponses(
        RecordingRequest $recordingRequest
    ): array
    {
        $unansweredResponses = [];

        foreach ($recordingRequest->getRecordingRequestResponses() as $recordingRequestResponse) {
            if (    $recordingRequestResponse->getStatus()
                === RecordingRequestResponseStatus::UNANSWERED
            ) {
                $unansweredResponses[] = $recordingRequestResponse;
            }
        }

        return $unansweredResponses;
    }

    public function getNumberOfAnsweredResponses(
        RecordingRequest $recordingRequest
    ): int
    {
        return sizeof($this->getAnsweredResponses($recordingRequest));
    }

    public function getAnsweredResponses(
        RecordingRequest $recordingRequest
    ): array
    {
        $answeredResponses = [];

        foreach ($recordingRequest->getRecordingRequestResponses() as $recordingRequestResponse) {
            if (    $recordingRequestResponse->getStatus()
                === RecordingRequestResponseStatus::ANSWERED
            ) {
                $answeredResponses[] = $recordingRequestResponse;
            }
        }

        return $answeredResponses;
    }

    public function getResponsesThatNeedToBeAnsweredByUser(
        User $user
    ): array
    {
        $responsesThatNeedToBeAnswered = [];

        foreach ($user->getRecordingRequestResponses() as $recordingRequestResponse) {
            if (    $recordingRequestResponse->getStatus()
                === RecordingRequestResponseStatus::UNANSWERED
            ) {
                $responsesThatNeedToBeAnswered[] = $recordingRequestResponse;
            }
        }

        return $responsesThatNeedToBeAnswered;
    }

    public function answerResponseWithVideo(
        RecordingRequestResponse $recordingRequestResponse,
        Video                    $video
    ): void
    {
        $recordingRequestResponse->addVideo($video);
        $recordingRequestResponse->setStatus(RecordingRequestResponseStatus::ANSWERED);

        $video->setRecordingRequestResponse($recordingRequestResponse);

        $this->entityManager->persist($recordingRequestResponse);
        $this->entityManager->persist($video);
        $this->entityManager->flush();
    }
}
