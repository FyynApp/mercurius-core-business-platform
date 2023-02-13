<?php

namespace App\VideoBasedMarketing\Presentationpages\Infrastructure\Service;

use App\Shared\Infrastructure\Service\FilesystemService;
use App\VideoBasedMarketing\Presentationpages\Domain\Entity\Presentationpage;
use App\VideoBasedMarketing\Presentationpages\Domain\Service\PresentationpagesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;


class WebpageScreenshotService
{
    private const ASSETS_SUBFOLDER_NAME = 'presentationpage-assets';

    private EntityManagerInterface $entityManager;

    private FilesystemService $filesystemService;

    private RouterInterface $router;

    private ParameterBagInterface $parameterBag;

    private PresentationpagesService $presentationpagesService;


    public function __construct(
        EntityManagerInterface   $entityManager,
        FilesystemService        $filesystemService,
        RouterInterface          $router,
        ParameterBagInterface    $parameterBag,
        PresentationpagesService $presentationpagesService
    )
    {
        $this->entityManager = $entityManager;
        $this->filesystemService = $filesystemService;
        $this->router = $router;
        $this->parameterBag = $parameterBag;
        $this->presentationpagesService = $presentationpagesService;
    }

    public function generateScreenshot(
        Presentationpage $presentationpage
    ): void
    {
        $this->createFilesystemStructuresForScreenshots($presentationpage);

        $targetUrl = $this->router->generate(
            'videobasedmarketing.presentationpages.presentation.screenshot_capture_view',
            [
                'presentationpageId' => $presentationpage->getId(),
                'presentationpageHash' => $this
                    ->presentationpagesService
                    ->generatePresentationpageHash($presentationpage)
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $contentStoragePath = $this->filesystemService->getContentStoragePath(
            [
                self::ASSETS_SUBFOLDER_NAME,
                $presentationpage->getId()
            ]
        );

        $generatedScreenshotFilePath = $this->filesystemService->getContentStoragePath(
            [
                self::ASSETS_SUBFOLDER_NAME,
                $presentationpage->getId(),
                'screenshot.webp'
            ]
        );

        $process = new Process(
            [
                'docker',
                'run',
                '-i',
                '--init',
                '--cap-add=SYS_ADMIN',
                '--rm',
                '--env',
                "TARGET_URL='$targetUrl'",
                '--env',
                'SCREENSHOT_FILE_PATH="/host/screenshot.webp"',
                '-v',
                "$contentStoragePath:/host",
                'ghcr.io/puppeteer/puppeteer:latest',
                'node',
                '-e',
                "`cat '{$this->parameterBag->get('kernel.project_dir')}"
                . 'src/VideoBasedMarketing/Presentationpages/Infrastructure/Resources/scripts/webpageScreenshotCapture.js'
                . "'`"
            ]
        );
        $process->setIdleTimeout(0);
        $process->setTimeout(60 * 5);
        $process->run();

        $fs = new Filesystem();
        $fs->copy(
            $generatedScreenshotFilePath,
            $this->filesystemService->getPublicWebfolderGeneratedContentPath(
                [
                    self::ASSETS_SUBFOLDER_NAME,
                    $presentationpage->getId(),
                    'screenshot.webp'
                ]
            ),
            true
        );

        $presentationpage->setHasScreenshot(true);
        $presentationpage->setScreenshotCaptureOutstanding(false);
        $this->entityManager->persist($presentationpage);
        $this->entityManager->flush();

        // Docker Container user owns the generated file, therefore he must delete it
        $process = new Process(
            [
                'docker',
                'run',
                '-i',
                '--init',
                '--cap-add=SYS_ADMIN',
                '--rm',
                '-v',
                "$contentStoragePath:/host",
                'ghcr.io/puppeteer/puppeteer:latest',
                'rm -f /host/screenshot.webp'
            ]
        );
        $process->run();
    }

    private function createFilesystemStructuresForScreenshots(
        Presentationpage $presentationpage
    ): void
    {
        $fs = new Filesystem();

        $fs->mkdir(
            $this->filesystemService->getContentStoragePath(
                [
                    self::ASSETS_SUBFOLDER_NAME,
                    $presentationpage->getId()
                ]
            )
        );

        $fs->mkdir(
            $this->filesystemService->getPublicWebfolderGeneratedContentPath(
                [
                    self::ASSETS_SUBFOLDER_NAME,
                    $presentationpage->getId()
                ]
            )
        );
    }
}
