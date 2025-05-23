<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\Service;

use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\Shared\Domain\Enum\Gender;
use App\Shared\Infrastructure\Enum\DateTimeFormat;
use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\Shared\Utility\ArrayUtility;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscription;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscriptionWebVtt;
use App\VideoBasedMarketing\AudioTranscription\Domain\Service\AudioTranscriptionDomainService;
use App\VideoBasedMarketing\LingoSync\Domain\Entity\LingoSyncProcess;
use App\VideoBasedMarketing\LingoSync\Domain\Entity\LingoSyncProcessTask;
use App\VideoBasedMarketing\LingoSync\Domain\Enum\LingoSyncProcessTaskStatus;
use App\VideoBasedMarketing\LingoSync\Domain\Enum\LingoSyncProcessTaskType;
use App\VideoBasedMarketing\LingoSync\Domain\SymfonyMessage\HandleTaskCommandSymfonyMessage;
use App\VideoBasedMarketing\LingoSync\Infrastructure\Service\LingoSyncInfrastructureService;
use App\VideoBasedMarketing\Organization\Domain\Entity\Organization;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use App\VideoBasedMarketing\Recordings\Infrastructure\SymfonyMessage\GenerateMissingVideoAssetsCommandSymfonyMessage;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;
use ValueError;


readonly class LingoSyncDomainService
{
    public function __construct(
        private EntityManagerInterface          $entityManager,
        private AudioTranscriptionDomainService $audioTranscriptionDomainService,
        private MessageBusInterface             $messageBus,
        private LingoSyncInfrastructureService  $lingoSyncInfrastructureService,
        private LingoSyncCreditsDomainService   $lingoSyncCreditsDomainService,
        private RecordingsInfrastructureService $recordingsInfrastructureService,
        private LoggerInterface                 $logger
    )
    {
    }

    /**
     * @throws Exception
     */
    public function countdownToLaunch(): string
    {
        $datetime1 = new DateTime();
        $datetime2 = new DateTime(
            '2023-06-25 10:00:00',
            new DateTimeZone('Etc/UTC')
        );
        $interval = $datetime1->diff($datetime2);
        return $interval->format('%d day, %h hours, and %i minutes');
    }

    public function videoHasRunningProcesses(Video $video): bool
    {
        foreach ($this->getProcessesForVideo($video) as $lingoSyncProcess) {
            if (   !$lingoSyncProcess->isFinished()
                && !$lingoSyncProcess->wasStopped()
                && !$lingoSyncProcess->hasErrored()
            ) {
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

        return $repo->findBy(
            ['video' => $video->getId()],
            ['createdAt' => Criteria::DESC]
        );
    }

    /**
     * @return Bcp47LanguageCode[]
     */
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
        if ($this->videoHasRunningProcesses($video)) {
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
    public function restartProcess(
        LingoSyncProcess $lingoSyncProcess
    ): LingoSyncProcess
    {
        if (   !$lingoSyncProcess->isFinished()
            && !$lingoSyncProcess->hasErrored()
            &&  $lingoSyncProcess->getAudioTranscriptionWasTriggered()
        ) {
            $this
                ->lingoSyncCreditsDomainService
                ->depleteCreditsFromLingoSyncProcess($lingoSyncProcess);
        }

        foreach ($lingoSyncProcess->getTasks() as $task) {
            if (   $task->getStatus() !== LingoSyncProcessTaskStatus::Finished
                && $task->getStatus() !== LingoSyncProcessTaskStatus::Errored
            ) {
                $task->setStatus(LingoSyncProcessTaskStatus::Stopped);
                $this->entityManager->persist($task);
                $this->entityManager->flush();
            }
        }

        return $this->startProcess(
            $lingoSyncProcess->getVideo(),
            $lingoSyncProcess->getOriginalLanguage(),
            $lingoSyncProcess->getOriginalGender(),
            $lingoSyncProcess->getTargetLanguages()
        );
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

        $existingWebVtts = $this->audioTranscriptionDomainService->getWebVtts(
            $generateOriginalLanguageTranscriptionTask->getLingoSyncProcess()->getVideo()
        );

        foreach ($existingWebVtts as $existingWebVtt) {
            if ($existingWebVtt->getBcp47LanguageCode() === $generateOriginalLanguageTranscriptionTask->getLingoSyncProcess()->getOriginalLanguage()) {
                $generateOriginalLanguageTranscriptionTask->getLingoSyncProcess()->setAudioTranscription($existingWebVtt->getAudioTranscription());
                $this->entityManager->persist($generateOriginalLanguageTranscriptionTask->getLingoSyncProcess());
                $this->entityManager->persist($existingWebVtt->getAudioTranscription());
                $this->entityManager->flush();
            }
        }

        if (is_null($generateOriginalLanguageTranscriptionTask->getLingoSyncProcess()->getAudioTranscription())) {
            // When the original language WebVTT becomes available through the Audio Transcription process,
            // a WebVttBecameAvailableSymfonyEvent will be dispatched, which will trigger the
            // handleWebVttBecameAvailable method below.
            $this->audioTranscriptionDomainService->startProcessingVideo(
                $generateOriginalLanguageTranscriptionTask->getLingoSyncProcess()->getVideo(),
                $generateOriginalLanguageTranscriptionTask->getLingoSyncProcess()->getOriginalLanguage(),
                $generateOriginalLanguageTranscriptionTask->getLingoSyncProcess()
            );

            $generateOriginalLanguageTranscriptionTask->setStatus(LingoSyncProcessTaskStatus::Running);

            $generateOriginalLanguageTranscriptionTask->getLingoSyncProcess()->setAudioTranscriptionWasTriggered(true);

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

                    $this->handleWebVttBecameAvailable($existingWebVtt);

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
                "Expected a GenerateTargetLanguageTranscription task with status '" . LingoSyncProcessTaskStatus::Initiated->value . "', but got '{$generateTargetLanguageTranscriptionTask->getStatus()->value}'."
            );
        }

        $generateTargetLanguageTranscriptionTask->setStatus(LingoSyncProcessTaskStatus::Running);
        $this->entityManager->persist($generateTargetLanguageTranscriptionTask);
        $this->entityManager->flush();

        $existingWebVtts = $this->audioTranscriptionDomainService->getWebVtts(
            $generateTargetLanguageTranscriptionTask->getLingoSyncProcess()->getVideo()
        );

        foreach ($existingWebVtts as $existingWebVtt) {
            if ($existingWebVtt->getBcp47LanguageCode() === $generateTargetLanguageTranscriptionTask->getLingoSyncProcess()->getOriginalLanguage()) {

                if (!$this->lingoSyncInfrastructureService::webVttIsValid($existingWebVtt->getVttContent())) {
                    $generateTargetLanguageTranscriptionTask->setStatus(LingoSyncProcessTaskStatus::Errored);
                    $generateTargetLanguageTranscriptionTask->setResult("Original language WebVTT content is not valid: '{$existingWebVtt->getVttContent()}'.");
                    $this->entityManager->persist($generateTargetLanguageTranscriptionTask);
                    $this->entityManager->flush();
                    $this->stopAllOpenTasks($generateTargetLanguageTranscriptionTask->getLingoSyncProcess());
                    return;
                }

                try {
                    $compactizedWebVttContent = $this->lingoSyncInfrastructureService::compactizeWebVtt(
                        $existingWebVtt->getVttContent()
                    );
                } catch (Throwable $t) {
                    $this->logger->error("Failed to compactize content of WebVTT '{$existingWebVtt->getId()}': '{$t->getMessage()}', at {$t->getFile()} line {$t->getLine()}.");
                    $generateTargetLanguageTranscriptionTask->setStatus(LingoSyncProcessTaskStatus::Errored);
                    $generateTargetLanguageTranscriptionTask->setResult("Could not compactize WebVTT '{$existingWebVtt->getId()}'.");
                    $this->entityManager->persist($generateTargetLanguageTranscriptionTask);
                    $this->entityManager->flush();
                    $this->stopAllOpenTasks($generateTargetLanguageTranscriptionTask->getLingoSyncProcess());
                    return;
                }

                if (!$this->lingoSyncInfrastructureService::webVttIsValid($compactizedWebVttContent)) {
                    $generateTargetLanguageTranscriptionTask->setStatus(LingoSyncProcessTaskStatus::Errored);
                    $generateTargetLanguageTranscriptionTask->setResult('Compactized original language WebVTT content is not valid.');
                    $this->entityManager->persist($generateTargetLanguageTranscriptionTask);
                    $this->entityManager->flush();
                    $this->stopAllOpenTasks($generateTargetLanguageTranscriptionTask->getLingoSyncProcess());
                    return;
                }

                $translatedWebVtt = $this->lingoSyncInfrastructureService->translateWebVtt(
                    $compactizedWebVttContent,
                    $generateTargetLanguageTranscriptionTask->getLingoSyncProcess()->getOriginalLanguage(),
                    $generateTargetLanguageTranscriptionTask->getTargetLanguage()
                );

                if (!$this->lingoSyncInfrastructureService::webVttIsValid($translatedWebVtt)) {
                    $generateTargetLanguageTranscriptionTask->setStatus(LingoSyncProcessTaskStatus::Errored);
                    $generateTargetLanguageTranscriptionTask->setResult('Translated WebVTT content is not valid.');
                    $this->entityManager->persist($generateTargetLanguageTranscriptionTask);
                    $this->entityManager->flush();
                    $this->stopAllOpenTasks($generateTargetLanguageTranscriptionTask->getLingoSyncProcess());
                    return;
                }

                $generateTargetLanguageTranscriptionTask->setResult($translatedWebVtt);
                $generateTargetLanguageTranscriptionTask->setStatus(LingoSyncProcessTaskStatus::Finished);

                $this->entityManager->persist($generateTargetLanguageTranscriptionTask->getLingoSyncProcess());
                $this->entityManager->persist($generateTargetLanguageTranscriptionTask);
                $this->entityManager->flush();

                $generateAudioSnippetsTask = $this->findProcessTask(
                    $generateTargetLanguageTranscriptionTask->getLingoSyncProcess(),
                    LingoSyncProcessTaskType::GenerateAudioSnippets,
                    $generateTargetLanguageTranscriptionTask->getTargetLanguage()
                );

                if (is_null($generateAudioSnippetsTask)) {
                    throw new Exception(
                        "Expected a GenerateAudioSnippets task for target language '{$generateTargetLanguageTranscriptionTask->getTargetLanguage()->value}', but none was found."
                    );
                }

                $this->messageBus->dispatch(new HandleTaskCommandSymfonyMessage(
                    $generateAudioSnippetsTask
                ));

                return;
            }
        }

        $generateTargetLanguageTranscriptionTask->setResult('Could not find original language transcription.');
        $generateTargetLanguageTranscriptionTask->setStatus(LingoSyncProcessTaskStatus::Errored);
        $this->entityManager->persist($generateTargetLanguageTranscriptionTask->getLingoSyncProcess());
        $this->entityManager->persist($generateTargetLanguageTranscriptionTask);
        $this->entityManager->flush();
        $this->stopAllOpenTasks($generateTargetLanguageTranscriptionTask->getLingoSyncProcess());
    }

    /**
     * @throws ValidationException
     * @throws ApiException
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

        $generateTargetLanguageTranscriptionTask = $this->findProcessTask(
            $createAudioSnippetsTask->getLingoSyncProcess(),
            LingoSyncProcessTaskType::GenerateTargetLanguageTranscription,
            $createAudioSnippetsTask->getTargetLanguage()
        );

        if (is_null($generateTargetLanguageTranscriptionTask)) {
            throw new ValueError(
                "Could not find a GenerateTargetLanguageTranscription task for LingoSyncProcess '{$createAudioSnippetsTask->getLingoSyncProcess()->getId()}' and language '{$createAudioSnippetsTask->getTargetLanguage()->value}'."
            );
        }

        if ($generateTargetLanguageTranscriptionTask->getStatus() !== LingoSyncProcessTaskStatus::Finished) {
            throw new ValueError(
                "Expected a GenerateTargetLanguageTranscription task with status '" . LingoSyncProcessTaskStatus::Finished->value . "', but got '{$generateTargetLanguageTranscriptionTask->getStatus()->value}'."
            );
        }

        if (is_null($generateTargetLanguageTranscriptionTask->getResult())) {
            throw new ValueError(
                "Expected a GenerateTargetLanguageTranscription task with a result, but got null."
            );
        }

        $createAudioSnippetsTask->setStatus(LingoSyncProcessTaskStatus::Running);
        $this->entityManager->persist($createAudioSnippetsTask);
        $this->entityManager->flush();

        try {
            $webVttContent = $generateTargetLanguageTranscriptionTask->getResult();

            $audioSnippetFilesFolderPath = $this->lingoSyncInfrastructureService->createAudioFilesForWebVttCues(
                $webVttContent,
                $createAudioSnippetsTask->getTargetLanguage(),
                $createAudioSnippetsTask->getLingoSyncProcess()->getOriginalGender(),
            );

            $createAudioSnippetsTask->setStatus(LingoSyncProcessTaskStatus::Finished);
            $this->entityManager->persist($createAudioSnippetsTask);
            $this->entityManager->flush();
        } catch (Throwable $t) {
            $createAudioSnippetsTask->setStatus(LingoSyncProcessTaskStatus::Errored);
            $createAudioSnippetsTask->setResult($t->getMessage());
            $this->entityManager->persist($createAudioSnippetsTask);
            $this->entityManager->flush();
            $this->stopAllOpenTasks($createAudioSnippetsTask->getLingoSyncProcess());
            return;
        }



        $generateConcatenatedAudioTask = $this->findProcessTask(
            $createAudioSnippetsTask->getLingoSyncProcess(),
            LingoSyncProcessTaskType::GenerateConcatenatedAudio,
            $createAudioSnippetsTask->getTargetLanguage()
        );

        if (is_null($generateConcatenatedAudioTask)) {
            throw new ValueError(
                "Could not find a GenerateConcatenatedAudio task for LingoSyncProcess '{$createAudioSnippetsTask->getLingoSyncProcess()->getId()}' and language '{$createAudioSnippetsTask->getTargetLanguage()->value}'."
            );
        }

        $generateConcatenatedAudioTask->setStatus(LingoSyncProcessTaskStatus::Running);
        $this->entityManager->persist($generateConcatenatedAudioTask);
        $this->entityManager->flush();

        try {
            $concatenatedAudioFilePath = $this->lingoSyncInfrastructureService->concatenateAudioFiles(
                $webVttContent,
                $audioSnippetFilesFolderPath
            );

            $generateConcatenatedAudioTask->setStatus(LingoSyncProcessTaskStatus::Finished);
            $this->entityManager->persist($generateTargetLanguageTranscriptionTask);
            $this->entityManager->flush();
        } catch (Throwable $t) {
            $generateConcatenatedAudioTask->setStatus(LingoSyncProcessTaskStatus::Errored);
            $generateConcatenatedAudioTask->setResult($t->getMessage());
            $this->entityManager->persist($generateConcatenatedAudioTask);
            $this->entityManager->flush();
            $this->stopAllOpenTasks($generateConcatenatedAudioTask->getLingoSyncProcess());
            return;
        }


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

        try {
            $originalVideo = $generateTranslatedVideoTask->getLingoSyncProcess()->getVideo();

            $translatedVideoPath = $this->lingoSyncInfrastructureService->createVideoFileFromVideoAndAudioFile(
                $originalVideo,
                $concatenatedAudioFilePath
            );

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

            $originalVideoAudioTranscription = $this->audioTranscriptionDomainService->getAudioTranscription($originalVideo);

            if (!is_null($originalVideoAudioTranscription)) {
                $newVideoAudioTranscription = new AudioTranscription(
                    $video,
                    $generateTranslatedVideoTask->getTargetLanguage()
                );

                $this->entityManager->persist($newVideoAudioTranscription);

                foreach ($this->audioTranscriptionDomainService->getWebVtts($originalVideo) as $webVtt) {
                    $newWebVtt = new AudioTranscriptionWebVtt(
                        $newVideoAudioTranscription,
                        $webVtt->getBcp47LanguageCode(),
                        $webVtt->getVttContent()
                    );
                    $this->entityManager->persist($newWebVtt);
                }

                $this->entityManager->flush();
            }

            $this
                ->recordingsInfrastructureService
                ->createAssetOriginalForInternallyCreatedVideo($video);

            $this->messageBus->dispatch(
                new GenerateMissingVideoAssetsCommandSymfonyMessage($video)
            );

            $generateTranslatedVideoTask->setStatus(LingoSyncProcessTaskStatus::Finished);
            $this->entityManager->persist($generateTranslatedVideoTask);
            $this->entityManager->flush();

            if ($generateTranslatedVideoTask->getLingoSyncProcess()->getAudioTranscriptionWasTriggered()) {
                $this->lingoSyncCreditsDomainService->depleteCreditsFromLingoSyncProcess(
                    $generateTranslatedVideoTask->getLingoSyncProcess()
                );
            }
        } catch (Throwable $t) {
            $generateTranslatedVideoTask->setStatus(LingoSyncProcessTaskStatus::Errored);
            $generateTranslatedVideoTask->setResult($t->getMessage());
            $this->entityManager->persist($generateTranslatedVideoTask);
            $this->entityManager->flush();
            $this->stopAllOpenTasks($generateTranslatedVideoTask->getLingoSyncProcess());
            return;
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
                "Audio Transcription '{$webVtt->getAudioTranscription()->getId()}' does not have a LingoSync Process."
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


        $tasks = $lingoSyncProcess->getTasks();

        foreach ($tasks as $task) {
            if (   $task->getType() === LingoSyncProcessTaskType::GenerateTargetLanguageTranscription
                && $task->getStatus() === LingoSyncProcessTaskStatus::Initiated
            ) {
                $this->messageBus->dispatch(new HandleTaskCommandSymfonyMessage(
                    $task
                ));
            }
        }
    }

    /**
     * @throws Exception
     */
    public function getNumberOfTranslatedSecondsDuringLastMonth(
        User $user
    ): float
    {
        $sql = "
            SELECT lsp.id AS id

            FROM {$this->entityManager->getClassMetadata(LingoSyncProcess::class)->getTableName()} lsp
            
            INNER JOIN {$this->entityManager->getClassMetadata(Video::class)->getTableName()} v
            ON v.id = lsp.videos_id
            
            INNER JOIN {$this->entityManager->getClassMetadata(Organization::class)->getTableName()} o
            ON o.id = v.organizations_id
            
            WHERE
                o.id = :oid
            ;
        ";
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->bindValue(':oid', $user->getCurrentlyActiveOrganization()->getId());
        $resultSet = $stmt->executeQuery();

        $seconds = 0.0;
        foreach ($resultSet->fetchAllAssociative() as $row) {
            /** @var LingoSyncProcess $process */
            $process = $this->entityManager->find(LingoSyncProcess::class, $row['id']);
            if ($process->getCreatedAt() < DateAndTimeService::getDateTime('-1 month')) {
                continue;
            }

            if ($process->hasErrored()) {
                continue;
            }

            $seconds += (float)$process->getVideo()->getSeconds();
        }

        return $seconds;
    }

    /**
     * @throws Exception
     * @return LingoSyncProcess[]
     */
    public function getProcessesNotOlderThan(
        User     $user,
        DateTime $dateTime
    ): array
    {
        $sql = "
            SELECT lsp.id AS id

            FROM {$this->entityManager->getClassMetadata(LingoSyncProcess::class)->getTableName()} lsp
            
            INNER JOIN {$this->entityManager->getClassMetadata(Video::class)->getTableName()} v
            ON v.id = lsp.videos_id
            
            INNER JOIN {$this->entityManager->getClassMetadata(Organization::class)->getTableName()} o
            ON o.id = v.organizations_id
            
            WHERE
                    o.id = :oid
                AND lsp.created_at > :dt
                
            ORDER BY lsp.created_at DESC
            ;
        ";
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->bindValue(':oid', $user->getCurrentlyActiveOrganization()->getId());
        $stmt->bindValue(':dt', $dateTime->format(DateTimeFormat::DatabaseFull->value));
        $resultSet = $stmt->executeQuery();

        $processes = [];
        foreach ($resultSet->fetchAllAssociative() as $row) {
            $processes[] = $this->entityManager->find(LingoSyncProcess::class, $row['id']);
        }

        return $processes;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function getTranslatedVideo(
        LingoSyncProcess $process
    ): ?Video
    {
        foreach ($process->getTasks() as $task) {
            if ($task->getType() === LingoSyncProcessTaskType::GenerateTranslatedVideo) {
                if ($task->getStatus() === LingoSyncProcessTaskStatus::Finished) {
                    $sql = "
                        SELECT v.id AS id
            
                        FROM {$this->entityManager->getClassMetadata(Video::class)->getTableName()} v
                        
                        INNER JOIN {$this->entityManager->getClassMetadata(LingoSyncProcessTask::class)->getTableName()} t
                        ON v.created_by_lingosync_process_tasks_id = t.id
                        
                        WHERE t.id = :tid
                        ;
                    ";
                    $stmt = $this->entityManager->getConnection()->prepare($sql);
                    $stmt->bindValue(':tid', $task->getId());
                    $resultSet = $stmt->executeQuery();

                    foreach ($resultSet->fetchAllAssociative() as $row) {
                        return $this->entityManager->find(Video::class, $row['id']);
                    }
                }
            }
        }
        return null;
    }

    public function getTotalNumberOfTasks(
        LingoSyncProcess $process
    ): int
    {
        return sizeof($process->getTasks());
    }

    public function getNumberOfFinishedTasks(
        LingoSyncProcess $process
    ): int
    {
        $numberOfFinishedTasks = 0;
        foreach ($process->getTasks() as $task) {
            if ($task->getStatus() === LingoSyncProcessTaskStatus::Finished) {
                $numberOfFinishedTasks++;
            }
        }
        return $numberOfFinishedTasks;
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

    /**
     * @throws Exception
     */
    private function stopAllOpenTasks(
        LingoSyncProcess $lingoSyncProcess
    ): void
    {
        foreach ($lingoSyncProcess->getTasks() as $task) {
            if (   $task->getStatus() !== LingoSyncProcessTaskStatus::Errored
                && $task->getStatus() !== LingoSyncProcessTaskStatus::Finished
            ) {
                $task->setStatus(LingoSyncProcessTaskStatus::Stopped);
                $this->entityManager->persist($task);
            }
        }
        $this->entityManager->flush();
    }
}
