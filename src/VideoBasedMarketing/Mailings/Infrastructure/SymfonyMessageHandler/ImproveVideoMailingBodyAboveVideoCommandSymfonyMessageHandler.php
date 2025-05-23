<?php

namespace App\VideoBasedMarketing\Mailings\Infrastructure\SymfonyMessageHandler;

use App\VideoBasedMarketing\Mailings\Domain\Entity\VideoMailing;
use App\VideoBasedMarketing\Mailings\Infrastructure\Service\OpenAiService;
use App\VideoBasedMarketing\Mailings\Infrastructure\SymfonyMessage\ImproveVideoMailingBodyAboveVideoCommandSymfonyMessage;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Throwable;


#[AsMessageHandler]
readonly class ImproveVideoMailingBodyAboveVideoCommandSymfonyMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface        $logger,
        private OpenAiService          $openAiService,
    )
    {
    }

    /** @throws Exception */
    public function __invoke(ImproveVideoMailingBodyAboveVideoCommandSymfonyMessage $message): void
    {
        try {
            $this->logger->debug("Received ImproveVideoMailingBodyAboveVideoCommandSymfonyMessage for videoMailing {$message->getVideoMailingId()}.");

            $videoMailing = $this->entityManager->find(VideoMailing::class, $message->getVideoMailingId());

            if (is_null($videoMailing)) {
                throw new UnrecoverableMessageHandlingException("Could not find videoMailing with id '{$message->getVideoMailingId()}'.");
            }

            $improvedText = $this
                ->openAiService
                ->improveTextForVideoMailing($videoMailing);

            $videoMailing->setImprovedBodyAboveVideo(trim($improvedText));
            $videoMailing->setImprovedBodyAboveVideoIsCurrentlyBeingGenerated(false);

            $this->entityManager->persist($videoMailing);
            $this->entityManager->flush();
        } catch (Throwable $t) {
            if (isset($videoMailing)) {
                $videoMailing->setImprovedBodyAboveVideo('');
                $videoMailing->setImprovedBodyAboveVideoIsCurrentlyBeingGenerated(false);
                $this->entityManager->persist($videoMailing);
                $this->entityManager->flush();
            }

            throw new UnrecoverableMessageHandlingException(
                "Could not improve text for videoMailing {$message->getVideoMailingId()}: {$t->getMessage()}.",
                0,
                $t
            );
        }
    }
}
