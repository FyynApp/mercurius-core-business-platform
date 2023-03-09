<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Service;

use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoSearchResult;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoSearchResultset;
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
    ): VideoSearchResultset
    {
        $q = trim($q);

        if ($q === '') {
            return new VideoSearchResultset([]);
        }

        $sql = "
            SELECT id
            FROM {$this->entityManager->getClassMetadata(Video::class)->getTableName()} v
            WHERE
            (
                MATCH(title) AGAINST (:qwildcard IN BOOLEAN MODE)
                OR MATCH(title) AGAINST (:q)
            )
            AND organizations_id = :organizationId
            AND is_deleted = FALSE
            ;
        ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $resultSet = $stmt->executeQuery([
            ':q' => $q,
            ':qwildcard' => "*$q*",
            ':organizationId' => $organization->getId()
        ]);

        $videoSearchResults = [];
        foreach ($resultSet->fetchAllAssociative() as $row) {
            $videoSearchResults[] = new VideoSearchResult(
                $this->entityManager->find(Video::class, $row['id'])
            );
        }

        return new VideoSearchResultset($videoSearchResults);
    }
}
