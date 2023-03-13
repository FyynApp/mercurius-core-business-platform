<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\MessageHandler;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscription;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscriptionWebVtt;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscriptionWord;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Entity\HappyScribeExport;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Enum\HappyScribeExportFormat;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Enum\HappyScribeExportState;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Message\CheckHappyScribeExportCommandMessage;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Service\HappyScribeApiService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

#[AsMessageHandler]
readonly class CheckHappyScribeExportCommandMessageHandler
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
        CheckHappyScribeExportCommandMessage $message
    ): void
    {
        /** @var null|HappyScribeExport $happyScribeExport */
        $happyScribeExport = $this->entityManager->find(
            AudioTranscription::class,
            $message->getHappyScribeExportId()
        );

        if (is_null($happyScribeExport)) {
            throw new UnrecoverableMessageHandlingException(
                "No Happy Scribe transcription with id '{$message->getHappyScribeExportId()}'."
            );
        }

        $this
            ->happyScribeApiService
            ->updateExport($happyScribeExport);

        $this->entityManager->persist($happyScribeExport);
        $this->entityManager->flush();

        if (   $happyScribeExport->getState() !== HappyScribeExportState::Failed
            || $happyScribeExport->getState() !== HappyScribeExportState::Expired
        ) {
            return;
        }

        if ($happyScribeExport->getState() !== HappyScribeExportState::Ready) {

            $this->messageBus->dispatch(
                new CheckHappyScribeExportCommandMessage(
                    $happyScribeExport
                ),
                [DelayStamp::delayUntil(
                    DateAndTimeService::getDateTime('+30 seconds')
                )]
            );
        } else {

            $downloadLink = $happyScribeExport->getDownloadLink();

            if (is_null($downloadLink)) {
                throw new UnrecoverableMessageHandlingException('Download link is null.');
            }

            $content = file_get_contents($downloadLink);

            if ($happyScribeExport->getFormat() === HappyScribeExportFormat::Vtt) {
                $vtt = new AudioTranscriptionWebVtt(
                    $happyScribeExport
                        ->getHappyScribeTranscription()
                        ->getAudioTranscription(),

                    $happyScribeExport
                        ->getHappyScribeTranscription()
                        ->getAudioTranscriptionBcp47LanguageCode(),

                    $content
                );

                $this->entityManager->persist($vtt);
                $this->entityManager->flush();
            }

            if ($happyScribeExport->getFormat() === HappyScribeExportFormat::Json) {

                $contentArray = json_decode($content, true);

                foreach ($contentArray as $speakerEntry) {
                    foreach ($speakerEntry['words'] as $wordEntry) {
                        $audioTranscriptionWord = new AudioTranscriptionWord(
                            $happyScribeExport->getHappyScribeTranscription()->getAudioTranscription(),
                            $happyScribeExport->getHappyScribeTranscription()->getAudioTranscriptionBcp47LanguageCode(),
                            $speakerEntry['speaker'],
                            $speakerEntry['speaker_number'],
                            $wordEntry['text'],
                            $wordEntry['type'],
                            $wordEntry['data_start'],
                            $wordEntry['data_end'],
                            $wordEntry['confidence']
                        );

                        $this->entityManager->persist($audioTranscriptionWord);
                    }
                    $this->entityManager->flush();
                }

            }
        }
    }
}
