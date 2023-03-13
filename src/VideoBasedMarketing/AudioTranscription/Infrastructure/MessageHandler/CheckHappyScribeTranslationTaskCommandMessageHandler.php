<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\MessageHandler;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscription;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Entity\HappyScribeTranscription;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Entity\HappyScribeTranslationTask;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Enum\HappyScribeTranscriptionState;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Enum\HappyScribeTranslationTaskState;
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
readonly class CheckHappyScribeTranslationTaskCommandMessageHandler
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
        CheckHappyScribeTranslationTaskCommandMessage $message
    ): void
    {
        /** @var null|HappyScribeTranslationTask $happyScribeTranslationTask */
        $happyScribeTranslationTask = $this->entityManager->find(
            AudioTranscription::class,
            $message->getHappyScribeTranslationTaskId()
        );

        if (is_null($happyScribeTranslationTask)) {
            throw new UnrecoverableMessageHandlingException(
                "No Happy Scribe translation task with id '{$message->getHappyScribeTranslationTaskId()}'."
            );
        }

        $this
            ->happyScribeApiService
            ->updateTranslationTask($happyScribeTranslationTask);

        $this->entityManager->persist($happyScribeTranslationTask);
        $this->entityManager->flush();

        if ($happyScribeTranslationTask->getState() === HappyScribeTranslationTaskState::Failed) {
            return;
        }

        if ($happyScribeTranslationTask->getState() !== HappyScribeTranslationTaskState::Done) {

            $this->messageBus->dispatch(
                new CheckHappyScribeTranslationTaskCommandMessage(
                    $happyScribeTranslationTask
                ),
                [DelayStamp::delayUntil(
                    DateAndTimeService::getDateTime('+30 seconds')
                )]
            );
        } else {

            $happyScribeTranscription = new HappyScribeTranscription(
                $happyScribeTranslationTask
                    ->getHappyScribeTranscription()
                    ->getAudioTranscription(),

                $happyScribeTranslationTask->getTranslatedTranscriptionId(),
                HappyScribeTranscriptionState::Initial,
                $happyScribeTranslationTask->getAudioTranscriptionBcp47LanguageCode()
            );

            $this->entityManager->persist($happyScribeTranscription);
            $this->entityManager->flush();

            $this->messageBus->dispatch(
                new CheckHappyScribeTranscriptionCommandMessage(
                    $happyScribeTranscription
                )
            );
        }
    }
}
