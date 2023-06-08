<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\SymfonyMessageHandler;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscription;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Enum\HappyScribeTranscriptionState;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\SymfonyMessage\CheckHappyScribeTranscriptionCommandSymfonyMessage;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\SymfonyMessage\CreateHappyScribeTranscriptionCommandSymfonyMessage;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Service\HappyScribeApiService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

#[AsMessageHandler]
readonly class CreateHappyScribeTranscriptionCommandSymfonyMessageHandler
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
        CreateHappyScribeTranscriptionCommandSymfonyMessage $message
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

        if (   $happyScribeTranscription->getState() !== HappyScribeTranscriptionState::Failed
            && $happyScribeTranscription->getState() !== HappyScribeTranscriptionState::Locked
        ) {

            $expectedDuration = $audioTranscription->getVideo()->getSeconds();
            if (is_null($expectedDuration)) {
                $expectedDuration = 60;
            }

            # https://help.happyscribe.com/en/articles/6087161-how-long-does-it-take-to-process-a-file
            $expectedDuration = (int)($expectedDuration / 2);

            $this->messageBus->dispatch(
                new CheckHappyScribeTranscriptionCommandSymfonyMessage(
                    $happyScribeTranscription
                ),
                [DelayStamp::delayUntil(
                    DateAndTimeService::getDateTime("+$expectedDuration seconds")
                )]
            );
        }
    }
}
