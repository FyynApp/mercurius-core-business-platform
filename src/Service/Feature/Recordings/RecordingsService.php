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
    public function getFinishedRecordingSessions(User $user): array
    {
        $sql = "
            SELECT id FROM {$this->entityManager->getClassMetadata(RecordingSession::class)->getTableName()}
            WHERE users_id = :users_id
            AND is_finished = 1
            ORDER BY created_at " . Criteria::DESC . "
            ;
        ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $resultSet = $stmt->executeQuery([':users_id' => $user->getId()]);

        $recordingSessions = [];
        foreach ($resultSet->fetchAllAssociative() as $row) {
            $recordingSessions[] = $this->entityManager->find(RecordingSession::class, $row['id']);
        }

        return $recordingSessions;
    }
}
