<?php

namespace App\MessageHandler\Feature\Recordings;

use App\Entity\Feature\Recordings\Video;
use App\Message\Feature\Recordings\GenerateMissingAssetsCommandMessage;
use App\Service\Feature\Recordings\VideoService;
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

    private VideoService $videoService;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface        $logger,
        VideoService           $videoService
    )
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->videoService = $videoService;
    }

    /** @throws Exception */
    public function __invoke(GenerateMissingAssetsCommandMessage $message): void
    {
        $this->logger->debug("Received GenerateMissingAssetsCommandMessage for video {$message->getVideoId()}.");

        $video = $this->entityManager->find(Video::class, $message->getVideoId());

        if (is_null($video)) {
            throw new UnrecoverableMessageHandlingException("Could not find video with id '{$message->getVideoId()}'.");
        }

        $this->videoService->generateMissingAssets($video);
    }
}
