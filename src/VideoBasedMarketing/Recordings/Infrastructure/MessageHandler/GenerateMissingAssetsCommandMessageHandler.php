<?php

namespace App\VideoBasedMarketing\Recordings\Infrastructure\MessageHandler;

use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use App\VideoBasedMarketing\Recordings\Infrastructure\Message\GenerateMissingVideoAssetsCommandMessage;
use App\VideoBasedMarketing\Recordings\Infrastructure\Service\RecordingsInfrastructureService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;


#[AsMessageHandler]
class GenerateMissingAssetsCommandMessageHandler
{
    private EntityManagerInterface $entityManager;

    private LoggerInterface $logger;

    private RecordingsInfrastructureService $recordingsInfrastructureService;

    public function __construct(
        EntityManagerInterface          $entityManager,
        LoggerInterface                 $logger,
        RecordingsInfrastructureService $recordingsInfrastructureService
    )
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->recordingsInfrastructureService = $recordingsInfrastructureService;
    }

    /** @throws Exception */
    public function __invoke(GenerateMissingVideoAssetsCommandMessage $message): void
    {
        $this->logger->debug("Received GenerateMissingAssetsCommandMessage for video {$message->getVideoId()}.");

        $video = $this->entityManager->find(Video::class, $message->getVideoId());

        if (is_null($video)) {
            throw new UnrecoverableMessageHandlingException("Could not find video with id '{$message->getVideoId()}'.");
        }

        $this->recordingsInfrastructureService->generateMissingVideoAssets($video);
    }
}
