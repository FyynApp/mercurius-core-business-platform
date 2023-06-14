<?php

namespace App\VideoBasedMarketing\AudioTranscription\Domain\Service;

use App\Shared\Domain\Enum\Iso639_1Code;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscription;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscriptionSuggestedSummary;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscriptionWebVtt;
use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\SymfonyMessage\CreateHappyScribeTranscriptionCommandSymfonyMessage;
use App\VideoBasedMarketing\LingoSync\Domain\Entity\LingoSyncProcess;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Messenger\MessageBusInterface;


readonly class AudioTranscriptionDomainService
{
    public function __construct(
        private MessageBusInterface    $messageBus,
        private EntityManagerInterface $entityManager
    )
    {
    }

    /**
     * @throws \Exception
     */
    public function startProcessingVideo(
        Video             $video,
        Bcp47LanguageCode $originalLanguage,
        ?LingoSyncProcess $lingoSyncProcess = null
    ): AudioTranscription
    {
        $audioTranscription = new AudioTranscription(
            $video,
            $originalLanguage,
            $lingoSyncProcess
        );

        $this->entityManager->persist($audioTranscription);
        $this->entityManager->flush();

        $this->messageBus->dispatch(
            new CreateHappyScribeTranscriptionCommandSymfonyMessage(
                $audioTranscription
            )
        );

        return $audioTranscription;
    }


    /**
     * @throws Exception
     */
    public function getAudioTranscription(
        Video $video
    ): ?AudioTranscription
    {
        $sql = "
            SELECT a.id AS id

            FROM {$this->entityManager->getClassMetadata(AudioTranscription::class)->getTableName()} a
            
            INNER JOIN {$this->entityManager->getClassMetadata(Video::class)->getTableName()} v
            ON v.id = a.videos_id
            
            WHERE
                v.id = :vid
            ;
        ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $resultSet = $stmt->executeQuery([':vid' => $video->getId()]);

        foreach ($resultSet->fetchAllAssociative() as $row) {
            return $this->entityManager->find(
                AudioTranscription::class,
                $row['id']
            );
        }

        return null;
    }


    /**
     * @return AudioTranscriptionWebVtt[]
     * @throws Exception
     */
    public function getWebVtts(
        Video $video
    ): array
    {
        $sql = "
                SELECT w.id AS id
                FROM {$this->entityManager->getClassMetadata(AudioTranscriptionWebVtt::class)->getTableName()} w
                
                INNER JOIN {$this->entityManager->getClassMetadata(AudioTranscription::class)->getTableName()} a
                ON a.id = w.audio_transcriptions_id
                
                INNER JOIN {$this->entityManager->getClassMetadata(Video::class)->getTableName()} v
                ON v.id = a.videos_id
                
                WHERE
                    v.id = :vid
                ;
            ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->bindValue(':vid', $video->getId());
        $resultSet = $stmt->executeQuery();

        $seenLanguages = [];
        $webVtts = [];
        foreach ($resultSet->fetchAllAssociative() as $row) {
            $vtt = $this->entityManager->find(
                AudioTranscriptionWebVtt::class,
                $row['id']
            );
            if (!in_array($vtt->getBcp47LanguageCode(), $seenLanguages)) {
                $webVtts[] = $vtt;
            }
            $seenLanguages[] = $vtt->getBcp47LanguageCode();
        }

        return $webVtts;
    }

    /**
     * @throws Exception
     */
    public function getSuggestedSummary(
        Video              $video,
        ?Iso639_1Code      $iso639_1Code,
        ?Bcp47LanguageCode $languageCode = null
    ): ?AudioTranscriptionSuggestedSummary
    {
        if (is_null($languageCode)) {
            if ($iso639_1Code === Iso639_1Code::De) {
                $languageCode = Bcp47LanguageCode::DeDe;
            } elseif ($iso639_1Code === Iso639_1Code::En) {
                $languageCode = Bcp47LanguageCode::EnUs;
            } else {
                $languageCode = Bcp47LanguageCode::EnUs;
            }
        }

        $sql = "
                SELECT s.id AS id
                FROM {$this->entityManager->getClassMetadata(AudioTranscriptionSuggestedSummary::class)->getTableName()} s
                
                INNER JOIN {$this->entityManager->getClassMetadata(AudioTranscription::class)->getTableName()} a
                ON a.id = s.audio_transcriptions_id
                
                INNER JOIN {$this->entityManager->getClassMetadata(Video::class)->getTableName()} v
                ON v.id = a.videos_id
                
                WHERE
                        v.id = :vid
                    AND s.bcp47_language_code = :slanguagecode
                ;
            ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $resultSet = $stmt->executeQuery([
            ':vid' => $video->getId(),
            ':slanguagecode' => $languageCode->value
        ]);

        foreach ($resultSet->fetchAllAssociative() as $row) {
            return $this->entityManager->find(
                AudioTranscriptionSuggestedSummary::class,
                $row['id']
            );
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function stillRunning(
        AudioTranscription $audioTranscription
    ): bool
    {
        return sizeof($this->getWebVtts($audioTranscription->getVideo())) === 0;
    }

    /**
     * @throws Exception
     */
    public function videoHasRunningTranscription(
        Video $video
    ): bool
    {
        /** @var ObjectRepository<AudioTranscription> $repo */
        $repo = $this->entityManager->getRepository(AudioTranscription::class);

        /** @var AudioTranscription[] $audioTranscriptions */
        $audioTranscriptions = $repo->findBy([
            'video' => $video->getId()
        ]);

        foreach ($audioTranscriptions as $audioTranscription) {
            if ($this->stillRunning($audioTranscription)) {
                return true;
            }
        }

        return false;
    }
}
