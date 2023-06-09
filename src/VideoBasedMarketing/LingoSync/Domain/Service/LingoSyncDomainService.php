<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\Service;

use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\Shared\Utility\ArrayUtility;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscriptionWebVtt;
use App\VideoBasedMarketing\AudioTranscription\Domain\Service\AudioTranscriptionDomainService;
use App\VideoBasedMarketing\LingoSync\Domain\Entity\LingoSyncProcess;
use App\VideoBasedMarketing\LingoSync\Domain\Entity\LingoSyncProcessTask;
use App\VideoBasedMarketing\LingoSync\Domain\Enum\LingoSyncProcessTaskStatus;
use App\VideoBasedMarketing\LingoSync\Domain\Enum\LingoSyncProcessTaskType;
use App\VideoBasedMarketing\LingoSync\Domain\SymfonyMessage\HandleTaskSymfonyMessageHandler;
use App\VideoBasedMarketing\LingoSync\Infrastructure\Service\LingoSyncInfrastructureService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use Symfony\Component\Messenger\MessageBusInterface;
use ValueError;


readonly class LingoSyncDomainService
{
    public function __construct(
        private EntityManagerInterface          $entityManager,
        private AudioTranscriptionDomainService $audioTranscriptionDomainService,
        private MessageBusInterface             $messageBus,
        private LingoSyncInfrastructureService $textToSpeechService
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
                    LingoSyncProcessTaskType::CreateAudioSnippets,
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
    public function handleTask(
        LingoSyncProcessTask $task
    ): void
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

        if ($task->getType() === LingoSyncProcessTaskType::CreateAudioSnippets) {
            $this->handleCreateAudioSnippetsTask($task);
        }
    }

    /**
     * @throws ValidationException
     * @throws ApiException|\Doctrine\DBAL\Exception
     * @throws Exception
     */
    private function handleCreateAudioSnippetsTask(
        LingoSyncProcessTask $createAudioSnippetsTask
    ): void
    {
        if ($createAudioSnippetsTask->getType() !== LingoSyncProcessTaskType::CreateAudioSnippets) {
            throw new ValueError(
                "Expected a CreateAudioSnippets task, but got '{$createAudioSnippetsTask->getType()->value}'."
            );
        }

        $webVtts = $this->audioTranscriptionDomainService->getWebVtts(
            $createAudioSnippetsTask->getLingoSyncProcess()->getAudioTranscription()->getVideo()
        );

        foreach ($webVtts as $webVtt) {
            if ($webVtt->getBcp47LanguageCode() === $createAudioSnippetsTask->getTargetLanguage()) {

                $createAudioSnippetsTask->setStatus(LingoSyncProcessTaskStatus::Running);
                $createAudioSnippetsTask->setLastHandledAt(DateAndTimeService::getDateTime());
                $this->entityManager->persist($createAudioSnippetsTask);
                $this->entityManager->flush();

                $audioFilesFolderPath = $this->textToSpeechService->createAudioFilesForWebVttCues(
                    $this->textToSpeechService::compactizeWebVtt($webVtt->getVttContent()),
                    $createAudioSnippetsTask->getTargetLanguage(),
                    $createAudioSnippetsTask->getLingoSyncProcess()->getOriginalGender(),
                );

                $concatenateAudioSnippetsTask = $this->findProcessTask(
                    $createAudioSnippetsTask->getLingoSyncProcess(),
                    LingoSyncProcessTaskType::ConcatenateAudioSnippets,
                    $createAudioSnippetsTask->getTargetLanguage()
                );

                if (is_null($concatenateAudioSnippetsTask)) {
                    throw new ValueError(
                        "Could not find a ConcatenateAudioSnippets task for LingoSyncProcess '{$createAudioSnippetsTask->getLingoSyncProcess()->getId()->toString()}' and language '{$createAudioSnippetsTask->getTargetLanguage()->value}'."
                    );
                }

                $createAudioSnippetsTask->setStatus(LingoSyncProcessTaskStatus::Finished);
                $this->entityManager->persist($createAudioSnippetsTask);
                $this->entityManager->flush();

                $concatenatedAudioFilePath = $this->textToSpeechService->concatenateAudioFiles(
                    $this->textToSpeechService::compactizeWebVtt($webVtt->getVttContent()),
                    $audioFilesFolderPath,
                    ''
                );



                #$this->textToSpeechService->
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
        $audioTranscription = $webVtt->getAudioTranscription();

        $lingoSyncProcess = $audioTranscription->getLingoSyncProcess();

        if ($lingoSyncProcess === null) {
            return;
        }

        $waitForTranslationTask = $this->findProcessTask(
            $lingoSyncProcess,
            LingoSyncProcessTaskType::WaitForTranslation,
            $webVtt->getBcp47LanguageCode()
        );

        if (is_null($waitForTranslationTask)) {
            return;
        }

        $waitForTranslationTask->setStatus(LingoSyncProcessTaskStatus::Finished);
        $waitForTranslationTask->setLastHandledAt(DateAndTimeService::getDateTime());

        $this->entityManager->persist($waitForTranslationTask);
        $this->entityManager->flush();

        $createAudioSnippetsTask = $this->findProcessTask(
            $lingoSyncProcess,
            LingoSyncProcessTaskType::CreateAudioSnippets,
            $webVtt->getBcp47LanguageCode()
        );

        if (is_null($createAudioSnippetsTask)) {
            throw new Exception(
                "Could not find 'CreateAudioSnippets' task for LingoSync process '{$lingoSyncProcess->getId()}' and target language '{$webVtt->getBcp47LanguageCode()->value}'."
            );
        }

        if ($createAudioSnippetsTask->getStatus() !== LingoSyncProcessTaskStatus::Initiated) {
            throw new Exception(
                "Expected CreateAudioSnippets task '{$createAudioSnippetsTask->getId()}' to be in status 'Initiated', but it is in status '{$createAudioSnippetsTask->getStatus()->value}'."
            );
        }

        $this->messageBus->dispatch(new HandleTaskSymfonyMessageHandler(
            $createAudioSnippetsTask
        ));
    }

    private function findProcessTask(
        LingoSyncProcess         $lingoSyncProcess,
        LingoSyncProcessTaskType $type,
        Bcp47LanguageCode        $targetLanguage
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
