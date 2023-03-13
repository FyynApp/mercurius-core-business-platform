<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\MessageHandler;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscription;
use App\VideoBasedMarketing\AudioTranscription\Domain\Enum\AudioTranscriptionBcp47LanguageCode;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Entity\HappyScribeTranscription;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Enum\HappyScribeExportFormat;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Enum\HappyScribeTranscriptionState;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Message\CheckHappyScribeExportCommandMessage;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Message\CheckHappyScribeTranscriptionCommandMessage;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Message\CheckHappyScribeTranslationTaskCommandMessage;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Service\HappyScribeApiService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

#[AsMessageHandler]
readonly class CheckHappyScribeTranscriptionCommandMessageHandler
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
     */
    public function __invoke(
        CheckHappyScribeTranscriptionCommandMessage $message
    ): void
    {
        /** @var null|HappyScribeTranscription $happyScribeTranscription */
        $happyScribeTranscription = $this->entityManager->find(
            AudioTranscription::class,
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
                new CheckHappyScribeTranscriptionCommandMessage(
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
                new CheckHappyScribeExportCommandMessage(
                    $happyScribeExport
                ),
                [DelayStamp::delayUntil(
                    DateAndTimeService::getDateTime('+30 seconds')
                )]
            );


            $happyScribeExport = $this
                ->happyScribeApiService
                ->createExport(
                    $happyScribeTranscription,
                    HappyScribeExportFormat::Json
                );

            $this->entityManager->persist($happyScribeExport);
            $this->entityManager->flush();

            $this->messageBus->dispatch(
                new CheckHappyScribeExportCommandMessage(
                    $happyScribeExport
                ),
                [DelayStamp::delayUntil(
                    DateAndTimeService::getDateTime('+30 seconds')
                )]
            );


            if (    $happyScribeTranscription->getAudioTranscriptionBcp47LanguageCode()
                === $happyScribeTranscription->getAudioTranscription()->getOriginalLanguageBcp47LanguageCode()
            ) {
                $happyScribeTranslationTask = $this
                    ->happyScribeApiService
                    ->createTranslationTask(
                        $happyScribeTranscription,
                        $happyScribeTranscription
                            ->getAudioTranscription()
                            ->getOriginalLanguageBcp47LanguageCode()
                        === AudioTranscriptionBcp47LanguageCode::DeDe

                            ? AudioTranscriptionBcp47LanguageCode::EnUs

                            : AudioTranscriptionBcp47LanguageCode::DeDe
                    );

                $this->entityManager->persist($happyScribeTranslationTask);
                $this->entityManager->flush();

                $this->messageBus->dispatch(
                    new CheckHappyScribeTranslationTaskCommandMessage(
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
