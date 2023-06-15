<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\Service;

use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\Shared\Domain\Enum\Gender;
use App\Shared\Utility\ArrayUtility;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscriptionWebVtt;
use App\VideoBasedMarketing\AudioTranscription\Domain\Service\AudioTranscriptionDomainService;
use App\VideoBasedMarketing\LingoSync\Domain\Entity\LingoSyncProcess;
use App\VideoBasedMarketing\LingoSync\Domain\Entity\LingoSyncProcessTask;
use App\VideoBasedMarketing\LingoSync\Domain\Enum\LingoSyncProcessTaskStatus;
use App\VideoBasedMarketing\LingoSync\Domain\Enum\LingoSyncProcessTaskType;
use App\VideoBasedMarketing\LingoSync\Domain\SymfonyMessage\HandleTaskCommandSymfonyMessage;
use App\VideoBasedMarketing\LingoSync\Infrastructure\Service\LingoSyncInfrastructureService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use App\VideoBasedMarketing\Recordings\Infrastructure\SymfonyMessage\GenerateMissingVideoAssetsCommandSymfonyMessage;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use ValueError;


readonly class LingoSyncDomainService
{
    public function __construct(
        private EntityManagerInterface          $entityManager,
        private AudioTranscriptionDomainService $audioTranscriptionDomainService,
        private MessageBusInterface             $messageBus,
        private LingoSyncInfrastructureService  $lingoSyncInfrastructureService,
        private RecordingsInfrastructureService $recordingsInfrastructureService,
        private LoggerInterface                 $logger
    )
    {
    }

    public function videoHasRunningProcess(Video $video): bool
    {
        foreach ($this->getProcessesForVideo($video) as $lingoSyncProcess) {
            if (!$lingoSyncProcess->isFinished()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return LingoSyncProcess[]
     */
    public function getProcessesForVideo(Video $video): array
    {
        /** @var ObjectRepository<LingoSyncProcess> $repo */
        $repo = $this->entityManager->getRepository(LingoSyncProcess::class);

        return $repo->findBy([
            'video' => $video->getId()
        ]);
    }

    public function getSupportedOriginalLanguages(): array
    {
        return Bcp47LanguageCode::cases();
    }

    public function getSupportedTargetLanguages(): array
    {
        return Bcp47LanguageCode::cases();
    }

    public function getSupportedOriginalGenders(): array
    {
        return Gender::cases();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function processCanBeStarted(
        Video $video
    ): bool
    {
        if ($this->videoHasRunningProcess($video)) {
            return false;
        }
        
        if ($this->audioTranscriptionDomainService->videoHasRunningTranscription($video)) {
            return false;
        }

        return true;
    }

    /**
     * @param Bcp47LanguageCode[] $targetLanguages
     * @throws Exception
     */
    public function startProcess(
        Video             $video,
        Bcp47LanguageCode $originalLanguage,
        Gender            $originalGender,
        array             $targetLanguages
    ): LingoSyncProcess
    {
        if (!$this->processCanBeStarted($video)) {
            throw new Exception(
                "LingoSync process for video '{$video->getId()}' cannot be started."
            );
        }

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
            $originalLanguage,
            $originalGender
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
                    LingoSyncProcessTaskType::GenerateTargetLanguageTranscription,
                    $targetLanguage
                )
            );

            $lingoSyncProcess->addTask(
                new LingoSyncProcessTask(
                    $lingoSyncProcess,
                    LingoSyncProcessTaskType::GenerateAudioSnippets,
                    $targetLanguage
                )
            );

            $lingoSyncProcess->addTask(
                new LingoSyncProcessTask(
                    $lingoSyncProcess,
                    LingoSyncProcessTaskType::GenerateConcatenatedAudio,
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

        $this->handleTask($generateOriginalLanguageTranscriptionTask);

        return $lingoSyncProcess;
    }


    /**
     * @throws Exception
     */
    public function handleTask(
        LingoSyncProcessTask $task
    ): void
    {
        if ($task->getType() === LingoSyncProcessTaskType::GenerateOriginalLanguageTranscription) {
            $this->handleTaskGenerateOriginalLanguageTranscription($task);
        }

        if ($task->getType() === LingoSyncProcessTaskType::GenerateTargetLanguageTranscription) {
            $this->handleTaskGenerateTargetLanguageTranscription($task);
        }

        if ($task->getType() === LingoSyncProcessTaskType::GenerateAudioSnippets) {
            $this->handleTaskGenerateAudioSnippets($task);
        }
    }

    /**
     * @throws Exception
     */
    private function handleTaskGenerateOriginalLanguageTranscription(
        LingoSyncProcessTask $generateOriginalLanguageTranscriptionTask
    ): void
    {
        if ($generateOriginalLanguageTranscriptionTask->getType() !== LingoSyncProcessTaskType::GenerateOriginalLanguageTranscription) {
            throw new ValueError(
                "Expected a GenerateOriginalLanguageTranscription task, but got '{$generateOriginalLanguageTranscriptionTask->getType()->value}'."
            );
        }

        if ($generateOriginalLanguageTranscriptionTask->getStatus() !== LingoSyncProcessTaskStatus::Initiated) {
            throw new Exception(
                "Expected a CreateAudioSnippets task with status '" . LingoSyncProcessTaskStatus::Initiated->value . "', but got '{$generateOriginalLanguageTranscriptionTask->getStatus()->value}'."
            );
        }

        if (is_null($generateOriginalLanguageTranscriptionTask->getLingoSyncProcess()->getAudioTranscription())) {
            $this->audioTranscriptionDomainService->startProcessingVideo(
                $generateOriginalLanguageTranscriptionTask->getLingoSyncProcess()->getVideo(),
                $generateOriginalLanguageTranscriptionTask->getLingoSyncProcess()->getOriginalLanguage(),
                $generateOriginalLanguageTranscriptionTask->getLingoSyncProcess()
            );

            $generateOriginalLanguageTranscriptionTask->setStatus(LingoSyncProcessTaskStatus::Running);

            $this->entityManager->persist($generateOriginalLanguageTranscriptionTask->getLingoSyncProcess());
            $this->entityManager->persist($generateOriginalLanguageTranscriptionTask);
            $this->entityManager->flush();

        } else {
            $existingWebVtts = $this->audioTranscriptionDomainService->getWebVtts(
                $generateOriginalLanguageTranscriptionTask->getLingoSyncProcess()->getVideo()
            );

            foreach ($existingWebVtts as $existingWebVtt) {
                if ($existingWebVtt->getBcp47LanguageCode() === $generateOriginalLanguageTranscriptionTask->getLingoSyncProcess()->getOriginalLanguage()) {
                    $generateOriginalLanguageTranscriptionTask->setStatus(LingoSyncProcessTaskStatus::Finished);

                    $this->entityManager->persist($generateOriginalLanguageTranscriptionTask->getLingoSyncProcess());
                    $this->entityManager->persist($generateOriginalLanguageTranscriptionTask);
                    $this->entityManager->flush();

                    return;
                }
            }

            throw new Exception(
                "Expected a WebVtt for language '{$generateOriginalLanguageTranscriptionTask->getLingoSyncProcess()->getOriginalLanguage()->value}', but none was found."
            );
        }
    }

    /**
     * @throws Exception
     */
    private function handleTaskGenerateTargetLanguageTranscription(
        LingoSyncProcessTask $generateTargetLanguageTranscriptionTask
    ): void
    {
        if ($generateTargetLanguageTranscriptionTask->getType() !== LingoSyncProcessTaskType::GenerateTargetLanguageTranscription) {
            throw new ValueError(
                "Expected a GenerateTargetLanguageTranscription task, but got '{$generateTargetLanguageTranscriptionTask->getType()->value}'."
            );
        }

        if ($generateTargetLanguageTranscriptionTask->getStatus() !== LingoSyncProcessTaskStatus::Initiated) {
            throw new Exception(
                "Expected a CreateAudioSnippets task with status '" . LingoSyncProcessTaskStatus::Initiated->value . "', but got '{$generateTargetLanguageTranscriptionTask->getStatus()->value}'."
            );
        }


    }

    /**
     * @throws ValidationException
     * @throws ApiException|\Doctrine\DBAL\Exception
     * @throws Exception
     */
    private function handleTaskGenerateAudioSnippets(
        LingoSyncProcessTask $createAudioSnippetsTask
    ): void
    {
        if ($createAudioSnippetsTask->getType() !== LingoSyncProcessTaskType::GenerateAudioSnippets) {
            throw new ValueError(
                "Expected a CreateAudioSnippets task, but got '{$createAudioSnippetsTask->getType()->value}'."
            );
        }

        if ($createAudioSnippetsTask->getStatus() !== LingoSyncProcessTaskStatus::Initiated) {
            throw new Exception(
                "Expected a CreateAudioSnippets task with status '" . LingoSyncProcessTaskStatus::Initiated->value . "', but got '{$createAudioSnippetsTask->getStatus()->value}'."
            );
        }

        $webVtts = $this->audioTranscriptionDomainService->getWebVtts(
            $createAudioSnippetsTask->getLingoSyncProcess()->getAudioTranscription()->getVideo()
        );

        $originalLanguageWebVttContent = null;
        foreach ($webVtts as $webVtt) {
            if ($webVtt->getBcp47LanguageCode() === $createAudioSnippetsTask->getLingoSyncProcess()->getOriginalLanguage()) {
                $originalLanguageWebVttContent = $this
                    ->lingoSyncInfrastructureService
                    ::compactizeWebVtt($webVtt->getVttContent());
            }
        }

        foreach ($webVtts as $webVtt) {
            if ($webVtt->getBcp47LanguageCode() === $createAudioSnippetsTask->getTargetLanguage()) {

                $createAudioSnippetsTask->setStatus(LingoSyncProcessTaskStatus::Running);
                $this->entityManager->persist($createAudioSnippetsTask);
                $this->entityManager->flush();

                $webVttContent = $this
                    ->lingoSyncInfrastructureService
                    ::compactizeWebVtt($webVtt->getVttContent());

                if (!is_null($originalLanguageWebVttContent)) {
                    $webVttContent = $this->lingoSyncInfrastructureService::mapWebVttTimestamps(
                        $originalLanguageWebVttContent,
                        $webVttContent
                    );
                }

                $audioSnippetFilesFolderPath = $this->lingoSyncInfrastructureService->createAudioFilesForWebVttCues(
                    $webVttContent,
                    $createAudioSnippetsTask->getTargetLanguage(),
                    $createAudioSnippetsTask->getLingoSyncProcess()->getOriginalGender(),
                );

                $createAudioSnippetsTask->setStatus(LingoSyncProcessTaskStatus::Finished);
                $this->entityManager->persist($createAudioSnippetsTask);
                $this->entityManager->flush();


                $concatenateAudioSnippetsTask = $this->findProcessTask(
                    $createAudioSnippetsTask->getLingoSyncProcess(),
                    LingoSyncProcessTaskType::GenerateConcatenatedAudio,
                    $createAudioSnippetsTask->getTargetLanguage()
                );

                if (is_null($concatenateAudioSnippetsTask)) {
                    throw new ValueError(
                        "Could not find a ConcatenateAudioSnippets task for LingoSyncProcess '{$createAudioSnippetsTask->getLingoSyncProcess()->getId()}' and language '{$createAudioSnippetsTask->getTargetLanguage()->value}'."
                    );
                }

                $concatenateAudioSnippetsTask->setStatus(LingoSyncProcessTaskStatus::Running);
                $this->entityManager->persist($concatenateAudioSnippetsTask);
                $this->entityManager->flush();

                $concatenatedAudioFilePath = $this->lingoSyncInfrastructureService->concatenateAudioFiles(
                    $webVttContent,
                    $audioSnippetFilesFolderPath
                );

                $concatenateAudioSnippetsTask->setStatus(LingoSyncProcessTaskStatus::Finished);
                $this->entityManager->persist($concatenateAudioSnippetsTask);
                $this->entityManager->flush();

                #echo "\nconcatenatedAudioFilePath is: $concatenatedAudioFilePath\n";


                $generateTranslatedVideoTask = $this->findProcessTask(
                    $createAudioSnippetsTask->getLingoSyncProcess(),
                    LingoSyncProcessTaskType::GenerateTranslatedVideo,
                    $createAudioSnippetsTask->getTargetLanguage()
                );

                if (is_null($generateTranslatedVideoTask)) {
                    throw new ValueError(
                        "Could not find a GenerateTranslatedVideo task for LingoSyncProcess '{$createAudioSnippetsTask->getLingoSyncProcess()->getId()}' and language '{$createAudioSnippetsTask->getTargetLanguage()->value}'."
                    );
                }

                $generateTranslatedVideoTask->setStatus(LingoSyncProcessTaskStatus::Running);
                $this->entityManager->persist($generateTranslatedVideoTask);
                $this->entityManager->flush();

                $translatedVideoPath = $this->lingoSyncInfrastructureService->createVideoFileFromVideoAndAudioFile(
                    $generateTranslatedVideoTask->getLingoSyncProcess()->getVideo(),
                    $concatenatedAudioFilePath
                );

                $generateTranslatedVideoTask->setStatus(LingoSyncProcessTaskStatus::Finished);
                $this->entityManager->persist($generateTranslatedVideoTask);
                $this->entityManager->flush();

                #echo "\ntranslatedVideoPath is: $translatedVideoPath\n";

                $video = new Video($createAudioSnippetsTask->getLingoSyncProcess()->getVideo()->getUser());
                $video->setInternallyCreatedSourceFilePath($translatedVideoPath);
                $video->setTitle(
                    $createAudioSnippetsTask->getLingoSyncProcess()->getVideo()->getTitle()
                    . " — LingoSync — {$generateTranslatedVideoTask->getTargetLanguage()->value}"
                );
                $video->setVideoFolder(
                    $createAudioSnippetsTask->getLingoSyncProcess()->getVideo()->getVideoFolder()
                );

                $video->setCreatedByLingoSyncProcessTask(
                    $generateTranslatedVideoTask
                );

                $this->entityManager->persist($video);
                $this->entityManager->flush();

                $this
                    ->recordingsInfrastructureService
                    ->createAssetOriginalForInternallyCreatedVideo($video);

                $this->messageBus->dispatch(
                    new GenerateMissingVideoAssetsCommandSymfonyMessage($video)
                );

                break;
            }
        }
    }

    /**
     * @throws Exception
     */
    public function handleWebVttBecameAvailable(
        AudioTranscriptionWebVtt $webVtt
    ): void
    {
        $this->logger->debug(
            "Web VTT '{$webVtt->getId()}' with language code '{$webVtt->getBcp47LanguageCode()->value}' from Audio Transcription '{$webVtt->getAudioTranscription()->getId()}' became available."
        );

        $this->entityManager->refresh($webVtt);
        $audioTranscription = $webVtt->getAudioTranscription();
        $lingoSyncProcess = $audioTranscription->getLingoSyncProcess();

        if (is_null($lingoSyncProcess)) {
            $this->logger->debug(
                "Audio Transcription '{$webVtt->getAudioTranscription()->getId()}' does not have a LingoSync Process, aborting."
            );
            return;
        }

        if ($webVtt->getBcp47LanguageCode() !== $lingoSyncProcess->getOriginalLanguage()) {
            return;
        }

        $generateOriginalLanguageTranscriptionTask = $this->findProcessTask(
            $lingoSyncProcess,
            LingoSyncProcessTaskType::GenerateOriginalLanguageTranscription,
            null,
        );
        $generateOriginalLanguageTranscriptionTask->setStatus(
            LingoSyncProcessTaskStatus::Finished
        );
        $this->entityManager->persist($generateOriginalLanguageTranscriptionTask);
        $this->entityManager->flush();


        $generateTargetLanguageTranscriptionTask = $this->findProcessTask(
            $lingoSyncProcess,
            LingoSyncProcessTaskType::GenerateTargetLanguageTranscription,
            $webVtt->getBcp47LanguageCode()
        );

        $this->messageBus->dispatch(new HandleTaskCommandSymfonyMessage(
            $generateTargetLanguageTranscriptionTask
        ));
    }


    private function findProcessTask(
        LingoSyncProcess         $lingoSyncProcess,
        LingoSyncProcessTaskType $type,
        ?Bcp47LanguageCode       $targetLanguage
    ): ?LingoSyncProcessTask
    {
        $tasks = $lingoSyncProcess->getTasks();

        foreach ($tasks as $task) {
            if (   $task->getType() === $type
                && $task->getTargetLanguage() === $targetLanguage
            ) {
                return $task;
            }
        }

        return null;
    }
}
