<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Service;

use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoFinderResult;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoFinderResultset;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;


readonly class VideoSearchDomainService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    )
    {
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function findVideosByTitle(
        string       $q,
        Organization $organization
    ): VideoFinderResultset
    {
        $q = trim($q);
        $q = mb_strtolower($q);
        $q = preg_replace(
            '/[^\p{L}0-9]/u',
            ' ',
            $q
        );
        $q = trim($q);

        if ($q === '') {
            return new VideoFinderResultset([]);
        }

        $sql = "
            SELECT id
            FROM {$this->entityManager->getClassMetadata(Video::class)->getTableName()} v
            WHERE
            (
                MATCH(title) AGAINST (:qwildcard IN BOOLEAN MODE)
                OR MATCH(title) AGAINST (:q)
                OR title LIKE :qlike
            )
            AND organizations_id = :organizationId
            AND is_deleted = FALSE
            
            LIMIT 50
            ;
        ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $resultSet = $stmt->executeQuery([
            ':q' => $q,
            ':qwildcard' => "*$q*",
            ':qlike' => "%$q%",
            ':organizationId' => $organization->getId()
        ]);

        $videoFinderResults = [];
        foreach ($resultSet->fetchAllAssociative() as $row) {
            $videoFinderResults[] = new VideoFinderResult(
                $this->entityManager->find(Video::class, $row['id'])
            );
        }

        return new VideoFinderResultset($videoFinderResults);
    }
}
