<?php

namespace App\VideoBasedMarketing\AudioTranscription\Domain\Service;


use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscription;
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

    public function getAudioTranscriptionProcessingState(
        AudioTranscription $audioTranscription
    ): AudioTranscriptionProcessingState {
        $this->entityManager->refresh($audioTranscription);

        $happyScribeTranscriptions = $audioTranscription->getHappyScribeTranscriptions();

        if ($happyScribeTranscriptions->count() === 0) {
            return AudioTranscriptionProcessingState::Started;
        }

        $allFinished = true;

        /** @var HappyScribeTranscription $happyScribeTranscription */
        foreach ($happyScribeTranscriptions as $happyScribeTranscription) {
            if (   $happyScribeTranscription->getState() === HappyScribeTranscriptionState::Failed
                || $happyScribeTranscription->getState() === HappyScribeTranscriptionState::Locked
            ) {
                return AudioTranscriptionProcessingState::Failed;
            }

            if ($happyScribeTranscription->getState() !== HappyScribeTranscriptionState::AutomaticDone) {
                $allFinished = false;
            }
        }

        if ($allFinished) {
            return AudioTranscriptionProcessingState::Finished;
        } else {
            return AudioTranscriptionProcessingState::PartlyFinished;
        }
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
}
