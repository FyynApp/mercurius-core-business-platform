<?php

namespace App\VideoBasedMarketing\RecordingRequests\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Enum\Role;
use App\VideoBasedMarketing\RecordingRequests\Domain\Entity\RecordingRequest;
use App\VideoBasedMarketing\RecordingRequests\Domain\Enum\RecordingRequestResponseStatus;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

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
}
