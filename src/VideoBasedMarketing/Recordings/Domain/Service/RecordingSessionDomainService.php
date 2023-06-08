<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Service;

use App\Shared\Infrastructure\Service\ShortIdService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Membership\Domain\Enum\MembershipPlanName;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Domain\SymfonyEvent\RecordingSessionWillBeRemovedSymfonyEvent;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use ValueError;


readonly class RecordingSessionDomainService
{
    public function __construct(
        private EntityManagerInterface          $entityManager,
        private VideoDomainService              $videoDomainService,
        private RecordingsInfrastructureService $recordingsInfrastructureService,
        private EventDispatcherInterface        $eventDispatcher,
        private ShortIdService                  $shortIdService,
        private MembershipService               $membershipService
    )
    {
    }


    /**
     * @throws Exception
     */
    public function handleRecordingSessionFinished(
        RecordingSession $recordingSession
    ): Video
    {
        if ($recordingSession->isFinished()) {
            return $recordingSession->getVideo();
        }

        if (is_null(
            $recordingSession
                ->getRecordingSessionVideoChunks()
                ->first()
        )) {
            throw new Exception(
                "Recording session '{$recordingSession->getId()}' does not have any video chunks."
            );
        }

        $recordingSession->setIsFinished(true);
        $this->entityManager->persist($recordingSession);
        $this->entityManager->flush();

        $video = $this
            ->videoDomainService
            ->createVideoEntityForFinishedRecordingSession($recordingSession);

        $this
            ->recordingsInfrastructureService
            ->checkAndHandleVideoAssetGenerationForUser(
                $recordingSession->getUser(),
                false,
                true
            );

        return $video;
    }

    /**
     * @throws Exception
     */
    public function startRecordingSession(User $user): RecordingSession
    {
        $recordingSession = new RecordingSession($user);
        $this->entityManager->persist($recordingSession);
        $this->shortIdService->encodeObject($recordingSession);
        $this->entityManager->flush();

        return $recordingSession;
    }

    public function removeRecordingSession(
        RecordingSession $recordingSession
    ): void
    {
        if (!is_null($recordingSession->getVideo())) {
            throw new ValueError("Recording session '{$recordingSession->getId()}' is already linked to a video and cannot be removed anymore.");
        }

        $this->eventDispatcher->dispatch(
            new RecordingSessionWillBeRemovedSymfonyEvent($recordingSession),
            RecordingSessionWillBeRemovedSymfonyEvent::class
        );

        $this->entityManager->remove($recordingSession);
        $this->entityManager->flush();
    }

    public function getMaxRecordingTime(
        User $user
    ): int
    {
        if (   $user->isAdmin()
            || $this->membershipService->getSubscribedMembershipPlanForCurrentlyActiveOrganization($user)->getName() === MembershipPlanName::Independent
            || $this->membershipService->getSubscribedMembershipPlanForCurrentlyActiveOrganization($user)->getName() === MembershipPlanName::Pro
        ) {
            return 3600;
        } else {
            return 300;
        }
    }
}
