<?php

namespace App\VideoBasedMarketing\AudioTranscription\Domain\Service;


use App\Shared\Domain\Enum\Iso639_1Code;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscription;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscriptionSuggestedSummary;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscriptionWebVtt;
use App\VideoBasedMarketing\AudioTranscription\Domain\Enum\AudioTranscriptionBcp47LanguageCode;
use App\VideoBasedMarketing\AudioTranscription\Domain\Enum\AudioTranscriptionProcessingState;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Entity\HappyScribeTranscription;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Enum\HappyScribeTranscriptionState;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Message\CreateHappyScribeTranscriptionCommandMessage;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class AudioTranscriptionDomainService
{
    public function __construct(
        private MessageBusInterface    $messageBus,
        private EntityManagerInterface $entityManager
    )
    {
    }

    public function startProcessingVideo(
        Video                               $video,
        AudioTranscriptionBcp47LanguageCode $audioTranscriptionBcp47LanguageCode
    ): void
    {
        $audioTranscription = new AudioTranscription(
            $video,
            $audioTranscriptionBcp47LanguageCode
        );

        $this->entityManager->persist($audioTranscription);
        $this->entityManager->flush();

        $this->messageBus->dispatch(
            new CreateHappyScribeTranscriptionCommandMessage(
                $audioTranscription
            )
        );
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
        $resultSet = $stmt->executeQuery([':vid' => $video->getId()]);

        $seenLanguages = [];
        $webVtts = [];
        foreach ($resultSet->fetchAllAssociative() as $row) {
            $vtt = $this->entityManager->find(
                AudioTranscriptionWebVtt::class,
                $row['id']
            );
            if (!in_array($vtt->getAudioTranscriptionBcp47LanguageCode(), $seenLanguages)) {
                $webVtts[] = $vtt;
            }
            $seenLanguages[] = $vtt->getAudioTranscriptionBcp47LanguageCode();
        }

        return $webVtts;
    }

    /**
     * @throws Exception
     */
    public function getSuggestedSummary(
        Video        $video,
        Iso639_1Code $iso639_1Code
    ): ?AudioTranscriptionSuggestedSummary
    {
        if ($iso639_1Code === Iso639_1Code::De) {
            $languageCode = AudioTranscriptionBcp47LanguageCode::DeDe;
        } elseif ($iso639_1Code === Iso639_1Code::En) {
            $languageCode = AudioTranscriptionBcp47LanguageCode::EnUs;
        } else {
            $languageCode = AudioTranscriptionBcp47LanguageCode::EnUs;
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
                    AND s.audio_transcription_bcp47_language_code = :slanguagecode
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

    public function stillRunning(
        AudioTranscription $audioTranscription
    ): bool
    {
        return sizeof($this->getWebVtts($audioTranscription->getVideo())) === 0;
    }
}
