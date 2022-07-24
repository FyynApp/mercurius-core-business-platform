<?php

namespace App\Service\Feature\Recordings;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Recordings\RecordingSession;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;

class RecordingsService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @throws Exception
     * @return RecordingSession[]
     */
    public function getRecordingSessionsWithFullVideo(User $user): array
    {
        $sql = "
            SELECT id FROM {$this->entityManager->getClassMetadata(RecordingSession::class)->getTableName()}
            WHERE users_id = :users_id
            ORDER BY created_at " . Criteria::DESC . "
            ;
        ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $resultSet = $stmt->executeQuery([':users_id' => $user->getId()]);

        $recordingSessions = [];
        foreach ($resultSet->fetchAllAssociative() as $row) {
            $recordingSessions[] = $this->entityManager->find(RecordingSession::class, $row['id']);
        }

        $results = [];
        foreach ($recordingSessions as $recordingSession) {
            if (!is_null($recordingSession->getRecordingSessionFullVideo()))
                $results[] = $recordingSession;
        }
        return $results;
    }
}
