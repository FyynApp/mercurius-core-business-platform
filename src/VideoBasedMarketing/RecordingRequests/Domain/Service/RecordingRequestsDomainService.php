<?php

namespace App\VideoBasedMarketing\RecordingRequests\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Enum\Role;
use App\VideoBasedMarketing\RecordingRequests\Domain\Entity\RecordingRequest;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class RecordingRequestsDomainService
{
    public function setUserAsRespondentForRecordingRequest(
        User             $user,
        RecordingRequest $recordingRequest
    ): void
    {
        if (!$user->hasRole(Role::RECORDING_REQUEST_RESPONDING_USER)) {
            throw new InvalidArgumentException(
                "User '{$user->getUserIdentifier()}' is not a user that can respond to a recording request."
            );
        }

        if (!is_null($user->getRespondingToRecordingRequest())) {
            throw new InvalidArgumentException(
                "User '{$user->getUserIdentifier()}' is already a respondent for recording request '{$user->getRespondingToRecordingRequest()->getId()}'."
            );
        }

        $user->setRespondingToRecordingRequest($recordingRequest);
        $recordingRequest->addRespondingUser($user);

        $this->entityManager->persist($user);
        $this->entityManager->persist($recordingRequest);

        $this->entityManager->flush();
    }

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
        if (!$user->hasRole(Role::RECORDING_REQUEST_RESPONDING_USER)) {
            throw new InvalidArgumentException(
                "User '{$user->getUserIdentifier()}' is not a user that can respond to a recording request."
            );
        }

        return $user->getRespondingToRecordingRequest();
    }

    public function userIsRespondentForRecordingRequest(
        User $user
    ): bool
    {
        return !is_null($user->getRespondingToRecordingRequest());
    }
}
