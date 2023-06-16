<?php

namespace App\VideoBasedMarketing\AudioTranscription\Infrastructure\SymfonyMessageHandler;

use App\Shared\Domain\Enum\Bcp47LanguageCode;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscriptionSuggestedSummary;
use App\VideoBasedMarketing\AudioTranscription\Domain\Entity\AudioTranscriptionWebVtt;
use App\VideoBasedMarketing\AudioTranscription\Infrastructure\SymfonyMessage\GenerateSuggestedSummaryCommandSymfonyMessage;
use App\VideoBasedMarketing\Mailings\Infrastructure\Service\OpenAiService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Throwable;


#[AsMessageHandler]
readonly class GenerateSuggestedSummaryCommandSymfonyMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface        $logger,
        private OpenAiService          $openAiService,
    )
    {
    }

    /** @throws Exception */
    public function __invoke(
        GenerateSuggestedSummaryCommandSymfonyMessage $message
    ): void
    {
        try {
            $webVtt = $this->entityManager->find(AudioTranscriptionWebVtt::class, $message->getWebVttId());

            if (is_null($webVtt)) {
                throw new UnrecoverableMessageHandlingException(
                    "Could not find audioTranscriptionWebVtt with id '{$message->getWebVttId()}'."
                );
            }

            if (!in_array(
                $webVtt->getBcp47LanguageCode(),
                [Bcp47LanguageCode::DeDe, Bcp47LanguageCode::EnUs]
            )) {
                return;
            }

            $summaryContent = $this
                ->openAiService
                ->summarizeWebVtt($webVtt);

            $summary = new AudioTranscriptionSuggestedSummary(
                $webVtt->getAudioTranscription(),
                $webVtt->getBcp47LanguageCode(),
                $summaryContent
            );

            $this->entityManager->persist($summary);
            $this->entityManager->flush();

        } catch (Throwable $t) {
            throw new UnrecoverableMessageHandlingException(
                "Could not create suggested summary for webVtt {$message->getWebVttId()}: {$t->getMessage()}.",
                0,
                $t
            );
        }
    }
}
