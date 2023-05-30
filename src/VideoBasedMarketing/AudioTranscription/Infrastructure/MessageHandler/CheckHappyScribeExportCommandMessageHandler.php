<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\MessageHandler;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscriptionWebVtt;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscriptionWord;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Entity\HappyScribeExport;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Enum\HappyScribeExportFormat;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Enum\HappyScribeExportState;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Message\CheckHappyScribeExportCommandMessage;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Message\GenerateSuggestedSummaryCommandMessage;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\Service\HappyScribeApiService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
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
        private MessageBusInterface    $messageBus,
        private LoggerInterface        $logger
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
            HappyScribeExport::class,
            $message->getHappyScribeExportId()
        );

        if (is_null($happyScribeExport)) {
            throw new UnrecoverableMessageHandlingException(
                "No Happy Scribe export with id '{$message->getHappyScribeExportId()}'."
            );
        }

        $this
            ->happyScribeApiService
            ->updateExport($happyScribeExport);

        $this->entityManager->persist($happyScribeExport);
        $this->entityManager->flush();

        if (   $happyScribeExport->getState() === HappyScribeExportState::Failed
            || $happyScribeExport->getState() === HappyScribeExportState::Expired
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

            $this->logger->debug("Downloaded content is: START>$content<END");

            if ($happyScribeExport->getFormat() === HappyScribeExportFormat::Vtt) {
                $vtt = new AudioTranscriptionWebVtt(
                    $happyScribeExport
                        ->getHappyScribeTranscription()
                        ->getAudioTranscription(),

                    $happyScribeExport
                        ->getHappyScribeTranscription()
                        ->getBcp47LanguageCode(),

                    $content
                );

                $this->entityManager->persist($vtt);
                $this->entityManager->flush();

                $this->messageBus->dispatch(
                    new GenerateSuggestedSummaryCommandMessage(
                        $vtt
                    )
                );
            }

            if ($happyScribeExport->getFormat() === HappyScribeExportFormat::Json) {

                if (!mb_check_encoding($content, 'UTF-8')) {
                    $this->logger->warning('$content is not valid UTTF-8!');
                    return;
                }

                $contentArray = json_decode(trim($content), true);

                if (!is_null($contentArray)) {
                    foreach ($contentArray as $key => $speakerEntry) {

                        $this->logger->debug('Speaker entry is ' . print_r($speakerEntry, true));
                        if (!is_null($speakerEntry['words'])) {
                            foreach ($speakerEntry['words'] as $wordEntry) {

                                $this->logger->debug('Word entry is ' . print_r($wordEntry, true));

                                $audioTranscriptionWord = new AudioTranscriptionWord(
                                    $happyScribeExport->getHappyScribeTranscription()->getAudioTranscription(),
                                    $happyScribeExport->getHappyScribeTranscription()->getBcp47LanguageCode(),
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
                        } else {
                            $this->logger->warning("contentArray[words] entry $key is null.");
                        }

                        $this->entityManager->flush();
                    }
                } else {
                    $this->logger->warning('$contentArray is null: ' . json_last_error_msg());
                }
            }
        }
    }
}
