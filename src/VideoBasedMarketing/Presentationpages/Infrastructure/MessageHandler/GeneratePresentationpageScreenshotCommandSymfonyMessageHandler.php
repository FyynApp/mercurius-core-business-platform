<?php

namespace App\VideoBasedMarketing\Presentationpages\Infrastructure\MessageHandler;

use App\VideoBasedMarketing\Presentationpages\Domain\Entity\Presentationpage;
use App\VideoBasedMarketing\Presentationpages\Infrastructure\Service\WebpageScreenshotService;
use App\VideoBasedMarketing\Presentationpages\Infrastructure\SymfonyMessage\GeneratePresentationpageScreenshotCommandSymfonyMessage;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;


#[AsMessageHandler]
class GeneratePresentationpageScreenshotCommandSymfonyMessageHandler
{
    private EntityManagerInterface $entityManager;

    private LoggerInterface $logger;

    private WebpageScreenshotService $webpageScreenshotService;

    public function __construct(
        EntityManagerInterface   $entityManager,
        LoggerInterface          $logger,
        WebpageScreenshotService $webpageScreenshotService
    )
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->webpageScreenshotService = $webpageScreenshotService;
    }

    /** @throws Exception */
    public function __invoke(GeneratePresentationpageScreenshotCommandSymfonyMessage $message): void
    {
        $this->logger->debug("Received GeneratePresentationpageScreenshotCommandSymfonyMessage for presentationpage {$message->getPresentationpageId()}.");

        $presentationpage = $this->entityManager->find(Presentationpage::class, $message->getPresentationpageId());

        if (is_null($presentationpage)) {
            throw new UnrecoverableMessageHandlingException("Could not find presentationpage with id '{$message->getPresentationpageId()}'.");
        }

        $this
            ->webpageScreenshotService
            ->generateScreenshot($presentationpage);
    }
}
