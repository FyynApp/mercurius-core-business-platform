<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\MessageHandler;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscription;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Enum\HappyScribeTranscriptionState;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Message\CheckHappyScribeTranscriptionCommandMessage;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Message\CreateHappyScribeTranscriptionCommandMessage;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Service\HappyScribeApiService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

#[AsMessageHandler]
readonly class CreateHappyScribeTranscriptionCommandMessageHandler
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
        CreateHappyScribeTranscriptionCommandMessage $message
    ): void
    {
        /** @var null|AudioTranscription $audioTranscription */
        $audioTranscription = $this->entityManager->find(
            AudioTranscription::class,
            $message->getAudioTranscriptionId()
        );

        if (is_null($audioTranscription)) {
            throw new UnrecoverableMessageHandlingException(
                "No audio transcription with id '{$message->getAudioTranscriptionId()}'."
            );
        }

        $happyScribeTranscription = $this
            ->happyScribeApiService
            ->createTranscription($audioTranscription);

        $this->entityManager->persist($happyScribeTranscription);
        $this->entityManager->flush();

        if ($happyScribeTranscription->getState() !== HappyScribeTranscriptionState::Failed) {

            $expectedDuration = $audioTranscription->getVideo()->getSeconds();
            if (is_null($expectedDuration)) {
                $expectedDuration = 60;
            }

            # https://help.happyscribe.com/en/articles/6087161-how-long-does-it-take-to-process-a-file
            $expectedDuration = (int)($expectedDuration / 2);

            $this->messageBus->dispatch(
                new CheckHappyScribeTranscriptionCommandMessage(
                    $happyScribeTranscription
                ),
                [DelayStamp::delayUntil(
                    DateAndTimeService::getDateTime("+$expectedDuration seconds")
                )]
            );
        }
    }
}
