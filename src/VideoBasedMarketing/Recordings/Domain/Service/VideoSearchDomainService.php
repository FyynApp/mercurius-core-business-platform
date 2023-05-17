<?php

namespace App\VideoBasedMarketing\Recordings\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Service\CapabilitiesService;
use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoFinderResult;
use App\VideoBasedMarketing\Recordings\Domain\Entity\VideoFinderResultset;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;


readonly class VideoSearchDomainService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CapabilitiesService    $capabilitiesService
    )
    {
    }

    /**
     * @throws DBALException
     * @throws Exception
     */
    public function findVideosByTitle(
        User         $searchingUser,
        string       $q,
        Organization $organization
    ): VideoFinderResultset
    {
        $q = trim($q);
        $q = mb_strtolower($q);
        $qForFulltext = preg_replace(
            '/[^\p{L}0-9]/u',
            ' ',
            $q
        );
        $qForFulltext = trim($qForFulltext);

        if ($q === '') {
            return new VideoFinderResultset([]);
        }

        $sql = "
            SELECT id
            FROM {$this->entityManager->getClassMetadata(Video::class)->getTableName()} v
            WHERE
            (
                   MATCH(title) AGAINST (:qwildcard IN BOOLEAN MODE)
                OR MATCH(title) AGAINST (:qliterally)
                OR title LIKE :qlike
            )
            AND organizations_id = :organizationId
            AND is_deleted = FALSE
            
            LIMIT 50
            ;
        ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $resultSet = $stmt->executeQuery([
            ':qwildcard' => "\"*$qForFulltext*\"",
            ':qliterally' => '"' . $qForFulltext . '"',
            ':qlike' => "%$q%",
            ':organizationId' => $organization->getId()
        ]);

        $videoFinderResults = [];
        foreach ($resultSet->fetchAllAssociative() as $row) {
            $videoFinderResults[] = new VideoFinderResult(
                $this->entityManager->find(Video::class, $row['id'])
            );
        }

        return $this->filterVideoFinderResultset(
            $searchingUser,
            new VideoFinderResultset($videoFinderResults)
        );
    }

    public function filterVideoFinderResultset(
        User                 $searchingUser,
        VideoFinderResultset $videoFinderResultset
    ): VideoFinderResultset
    {
        if ($this->capabilitiesService->canSeeFoldersNotVisibleForNonAdministrators($searchingUser)) {
            return $videoFinderResultset;
        }

        $results = [];
        foreach ($videoFinderResultset->getResults() as $result) {
            if ($result->video->isFolderOrParentVisibleForNonAdministrators()) {
                $results[] = $result;
            }
        }
        return new VideoFinderResultset($results);
    }
}
