<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\Service;

use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\Shared\Utility\ArrayUtility;
use App\VideoBasedMarketing\LingoSync\Domain\Entity\LingoSyncProcess;
use App\VideoBasedMarketing\LingoSync\Domain\Entity\LingoSyncProcessTask;
use App\VideoBasedMarketing\LingoSync\Domain\Enum\LingoSyncProcessTaskType;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use ValueError;


readonly class LingoSyncDomainService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * @throws Exception
     * @param Bcp47LanguageCode[] $targetLanguages
     */
    public function createLingoSyncProcess(
        Video             $video,
        Bcp47LanguageCode $originalLanguage,
        array             $targetLanguages
    ): LingoSyncProcess
    {
        if (!ArrayUtility::allValuesAreClass($targetLanguages, Bcp47LanguageCode::class)) {
            throw new ValueError(
                'Expected an array of Bcp47LanguageCode objects, but got ' . json_encode($targetLanguages) . '.'
            );
        }

        $lingoSyncProcess = new LingoSyncProcess(
            $video,
            $originalLanguage
        );

        $generateOriginalLanguageTranscriptionTask = new LingoSyncProcessTask(
            $lingoSyncProcess,
            LingoSyncProcessTaskType::GenerateOriginalLanguageTranscription,
            null
        );

        $lingoSyncProcess->addTask(
            $generateOriginalLanguageTranscriptionTask
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

        return $lingoSyncProcess;
    }


    public function handleTask(LingoSyncProcessTask $task): void
    {
        if ($task->getType() === LingoSyncProcessTaskType::GenerateOriginalLanguageTranscription) {

        }
    }
}
