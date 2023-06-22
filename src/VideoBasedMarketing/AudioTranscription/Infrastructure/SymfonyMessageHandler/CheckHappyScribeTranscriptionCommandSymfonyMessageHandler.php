<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\SymfonyMessageHandler;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Entity\HappyScribeTranscription;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Enum\HappyScribeExportFormat;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Enum\HappyScribeTranscriptionState;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\SymfonyMessage\CheckHappyScribeExportCommandSymfonyMessage;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\SymfonyMessage\CheckHappyScribeTranscriptionCommandSymfonyMessage;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\SymfonyMessage\CheckHappyScribeTranslationTaskCommandSymfonyMessage;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Service\HappyScribeApiService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Throwable;

#[AsMessageHandler]
readonly class CheckHappyScribeTranscriptionCommandSymfonyMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private HappyScribeApiService  $happyScribeApiService,
        private MessageBusInterface    $messageBus
    )
    {
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(
        CheckHappyScribeTranscriptionCommandSymfonyMessage $message
    ): void
    {
        /** @var null|HappyScribeTranscription $happyScribeTranscription */
        $happyScribeTranscription = $this->entityManager->find(
            HappyScribeTranscription::class,
            $message->getHappyScribeTranscriptionId()
        );

        if (is_null($happyScribeTranscription)) {
            throw new UnrecoverableMessageHandlingException(
                "No Happy Scribe transcription with id '{$message->getHappyScribeTranscriptionId()}'."
            );
        }

        $this
            ->happyScribeApiService
            ->updateTranscription($happyScribeTranscription);

        $this->entityManager->persist($happyScribeTranscription);
        $this->entityManager->flush();

        if (   $happyScribeTranscription->getState() === HappyScribeTranscriptionState::Failed
            || $happyScribeTranscription->getState() === HappyScribeTranscriptionState::Locked
        ) {
            return;
        }

        if ($happyScribeTranscription->getState() !== HappyScribeTranscriptionState::AutomaticDone) {

            $this->messageBus->dispatch(
                new CheckHappyScribeTranscriptionCommandSymfonyMessage(
                    $happyScribeTranscription
                ),
                [DelayStamp::delayUntil(
                    DateAndTimeService::getDateTime('+30 seconds')
                )]
            );

        } else {

            $happyScribeExport = $this
                ->happyScribeApiService
                ->createExport(
                    $happyScribeTranscription,
                    HappyScribeExportFormat::Vtt
                );

            $this->entityManager->persist($happyScribeExport);
            $this->entityManager->flush();

            $this->messageBus->dispatch(
                new CheckHappyScribeExportCommandSymfonyMessage(
                    $happyScribeExport
                ),
                [DelayStamp::delayUntil(
                    DateAndTimeService::getDateTime('+30 seconds')
                )]
            );


            /* Inactive because we cannot parse the resulting JSON
            $happyScribeExport = $this
                ->happyScribeApiService
                ->createExport(
                    $happyScribeTranscription,
                    HappyScribeExportFormat::Json
                );

            $this->entityManager->persist($happyScribeExport);
            $this->entityManager->flush();

            $this->messageBus->dispatch(
                new CheckHappyScribeExportCommandSymfonyMessage(
                    $happyScribeExport
                ),
                [DelayStamp::delayUntil(
                    DateAndTimeService::getDateTime('+30 seconds')
                )]
            );
            */

            if (    $happyScribeTranscription->getBcp47LanguageCode()
                === $happyScribeTranscription->getAudioTranscription()->getOriginalLanguageBcp47LanguageCode()
            ) {
                foreach (Bcp47LanguageCode::cases() as $languageCode) {
                    if ($languageCode === $happyScribeTranscription->getBcp47LanguageCode()) {
                        continue;
                    }

                    $happyScribeTranslationTask = $this
                        ->happyScribeApiService
                        ->createTranslationTask(
                            $happyScribeTranscription,
                            $languageCode
                        );

                    $this->entityManager->persist($happyScribeTranslationTask);
                    $this->entityManager->flush();

                    $this->messageBus->dispatch(
                        new CheckHappyScribeTranslationTaskCommandSymfonyMessage(
                            $happyScribeTranslationTask
                        ),
                        [DelayStamp::delayUntil(
                            DateAndTimeService::getDateTime('+30 seconds')
                        )]
                    );
                }
            }
        }
    }
}
