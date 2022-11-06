<?php

namespace App\VideoBasedMarketing\Presentationpages\Infrastructure\MessageHandler;

use App\Entity\Feature\Presentationpages\Presentationpage;
use App\VideoBasedMarketing\Presentationpages\Domain\Service\PresentationpagesService;
use App\VideoBasedMarketing\Presentationpages\Infrastructure\Message\GeneratePresentationpageScreenshotCommandMessage;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;


#[AsMessageHandler]
class GeneratePresentationpageScreenshotCommandMessageHandler
{
    private EntityManagerInterface $entityManager;

    private LoggerInterface $logger;

    private PresentationpagesService $presentationpageService;

    public function __construct(
        EntityManagerInterface   $entityManager,
        LoggerInterface          $logger,
        PresentationpagesService $presentationpageService
    )
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->presentationpageService = $presentationpageService;
    }

    /** @throws Exception */
    public function __invoke(GeneratePresentationpageScreenshotCommandMessage $message): void
    {
        $this->logger->debug("Received GeneratePresentationpageScreenshotCommandMessage for presentationpage {$message->getPresentationpageId()}.");

        $presentationpage = $this->entityManager->find(Presentationpage::class, $message->getPresentationpageId());

        if (is_null($presentationpage)) {
            throw new UnrecoverableMessageHandlingException("Could not find presentationpage with id '{$message->getPresentationpageId()}'.");
        }

        $this->presentationpageService->generateScreenshot($presentationpage);
    }
}
