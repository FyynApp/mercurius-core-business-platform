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
use Psr\Log\LoggerInterface;
use Throwable;


readonly class ProcessLogService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface        $logger
    )
    {
    }

    public function createEntry(
        ProcessLogEntryType $processLogEntryType,
        ?User               $user = null,
        ?Organization       $organization = null,
        ?RecordingSession   $recordingSession = null,
        ?VideoUpload        $videoUpload = null,
        ?Video              $video = null,
    ): ?ProcessLogEntry
    {
        try {
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
        } catch (Throwable $throwable) {
            $this->logger->error($throwable->getMessage());
            return null;
        }
    }

    public function markEntryAsFinishedSuccessfully(
        ?ProcessLogEntry $entry
    ): void
    {
        if (is_null($entry)) {
            return;
        }
        try {
            $entry->setFinishedAt(DateAndTimeService::getDateTime());
            $this->entityManager->persist($entry);
            $this->entityManager->flush();
        } catch (Throwable $throwable) {
            $this->logger->error($throwable->getMessage());
        }
    }

    public function markEntryAsFinishedWithError(
        ?ProcessLogEntry $entry,
        string          $latestErrorMessage
    ): void
    {
        if (is_null($entry)) {
            return;
        }
        try {
            $entry->setFinishedAt(DateAndTimeService::getDateTime());
            $entry->setLatestErrorMessage($latestErrorMessage);
            $this->entityManager->persist($entry);
            $this->entityManager->flush();
        } catch (Throwable $throwable) {
            $this->logger->error($throwable->getMessage());
        }
    }
}
