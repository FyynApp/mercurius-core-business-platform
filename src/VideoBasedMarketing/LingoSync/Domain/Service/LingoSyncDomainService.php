<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\Service;

use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\Shared\Utility\ArrayUtility;
use App\VideoBasedMarketing\AudioTranscription\Domain\Service\AudioTranscriptionDomainService;
use App\VideoBasedMarketing\LingoSync\Domain\Entity\LingoSyncProcess;
use App\VideoBasedMarketing\LingoSync\Domain\Entity\LingoSyncProcessTask;
use App\VideoBasedMarketing\LingoSync\Domain\Enum\LingoSyncProcessTaskStatus;
use App\VideoBasedMarketing\LingoSync\Domain\Enum\LingoSyncProcessTaskType;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use ValueError;


readonly class LingoSyncDomainService
{
    public function __construct(
        private EntityManagerInterface          $entityManager,
        private AudioTranscriptionDomainService $audioTranscriptionDomainService
    ) {}

    /**
     * @throws Exception
     * @param Bcp47LanguageCode[] $targetLanguages
     */
    public function startLingoSyncProcess(
        Video             $video,
        Bcp47LanguageCode $originalLanguage,
        array             $targetLanguages
    ): LingoSyncProcess
    {
        // @TODO: This here triggers an Audio Transcription process,
        // which in turn will fire an AudioTranscriptionCompletedEvent.
        // We need to listen to that event and then start the LingoSyncProcess proper.

        if (!ArrayUtility::allValuesAreOfClass($targetLanguages, Bcp47LanguageCode::class)) {
            throw new ValueError(
                'Expected an array of Bcp47LanguageCode objects, but got ' . json_encode($targetLanguages) . '.'
            );
        }

        if (sizeof($targetLanguages) === 0) {
            throw new ValueError(
                'Need at least one target language.'
            );
        }

        $lingoSyncProcess = new LingoSyncProcess(
            $video,
            $originalLanguage
        );

        $generateAudioTranscriptionTask = new LingoSyncProcessTask(
            $lingoSyncProcess,
            LingoSyncProcessTaskType::GenerateAudioTranscription,
            null
        );

        $lingoSyncProcess->addTask(
            $generateAudioTranscriptionTask
        );

        foreach ($targetLanguages as $targetLanguage) {
            $lingoSyncProcess->addTask(
                new LingoSyncProcessTask(
                    $lingoSyncProcess,
                    LingoSyncProcessTaskType::WaitForTranslation,
                    $targetLanguage
                )
            );

            $lingoSyncProcess->addTask(
                new LingoSyncProcessTask(
                    $lingoSyncProcess,
                    LingoSyncProcessTaskType::CreateAudioSnippet,
                    $targetLanguage
                )
            );

            $lingoSyncProcess->addTask(
                new LingoSyncProcessTask(
                    $lingoSyncProcess,
                    LingoSyncProcessTaskType::ConcatenateAudioSnippets,
                    $targetLanguage
                )
            );

            $lingoSyncProcess->addTask(
                new LingoSyncProcessTask(
                    $lingoSyncProcess,
                    LingoSyncProcessTaskType::GenerateTranslatedVideo,
                    $targetLanguage
                )
            );
        }

        $this->entityManager->persist($lingoSyncProcess);
        $this->entityManager->flush();

        $this->handleTask($generateAudioTranscriptionTask);

        return $lingoSyncProcess;
    }


    /**
     * @throws Exception
     */
    public function handleTask(LingoSyncProcessTask $task): void
    {
        if ($task->getType() === LingoSyncProcessTaskType::GenerateAudioTranscription) {

            if ($task->getStatus() === LingoSyncProcessTaskStatus::Initiated) {
                $audioTranscription = $this->audioTranscriptionDomainService->startProcessingVideo(
                    $task->getLingoSyncProcess()->getVideo(),
                    $task->getLingoSyncProcess()->getOriginalLanguage(),
                    $task->getLingoSyncProcess()
                );

                $task->getLingoSyncProcess()->setAudioTranscription($audioTranscription);
                $task->setStatus(LingoSyncProcessTaskStatus::Running);

                $this->entityManager->persist($task->getLingoSyncProcess());
                $this->entityManager->flush();
            }
        }
    }
}
