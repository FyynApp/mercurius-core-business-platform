<?php

namespace App\Shared\Infrastructure\Service;

use App\Shared\Infrastructure\Entity\ProcessLogEntry;
use App\Shared\Infrastructure\Enum\ProcessLogEntryType;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Infrastructure\Entity\VideoUpload;
use Doctrine\ORM\EntityManagerInterface;
use Exception;


readonly class ProcessLogService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    )
    {
    }

    /**
     * @throws Exception
     */
    public function createEntry(
        ProcessLogEntryType $processLogEntryType,
        ?User               $user = null,
        ?Organization       $organization = null,
        ?RecordingSession   $recordingSession = null,
        ?VideoUpload        $videoUpload = null,
        ?Video              $video = null,
    ): ProcessLogEntry
    {
        $entry = new ProcessLogEntry(
            $processLogEntryType,
            $user,
            $organization,
            $recordingSession,
            $videoUpload,
            $video
        );

        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        return $entry;
    }

    /**
     * @throws Exception
     */
    public function markEntryAsFinishedSuccessfully(
        ProcessLogEntry $entry
    ): void
    {
        $entry->setFinishedAt(DateAndTimeService::getDateTime());
        $this->entityManager->persist($entry);
        $this->entityManager->flush();
    }

    /**
     * @throws Exception
     */
    public function markEntryAsFinishedWithError(
        ProcessLogEntry $entry,
        string          $latestErrorMessage
    ): void
    {
        $entry->setFinishedAt(DateAndTimeService::getDateTime());
        $entry->setLatestErrorMessage($latestErrorMessage);
        $this->entityManager->persist($entry);
        $this->entityManager->flush();
    }
}
