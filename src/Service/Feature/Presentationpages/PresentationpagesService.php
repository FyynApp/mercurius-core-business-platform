<?php

namespace App\Service\Feature\Presentationpages;

use App\Entity\Feature\Presentationpages\BgColor;
use App\Entity\Feature\Presentationpages\FgColor;
use App\Entity\Feature\Presentationpages\Presentationpage;
use App\Entity\Feature\Presentationpages\PresentationpageCategory;
use App\Entity\Feature\Presentationpages\PresentationpageElement;
use App\Entity\Feature\Presentationpages\PresentationpageElementVariant;
use App\Entity\Feature\Presentationpages\PresentationpageType;
use App\Entity\Feature\Presentationpages\TextColor;
use App\Entity\Feature\Recordings\Video;
use App\Message\Feature\Presentationpages\GeneratePresentationpageScreenshotCommandMessage;
use App\Shared\DateAndTime\Infrastructure\Service\DateAndTimeService;
use App\Shared\Filesystem\Infrastructure\Service\FilesystemService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


class PresentationpagesService
{
    private const ASSETS_SUBFOLDER_NAME = 'presentationpage-assets';

    private EntityManagerInterface $entityManager;

    private TranslatorInterface $translator;

    private FilesystemService $filesystemService;

    private MessageBusInterface $messageBus;

    private RouterInterface $router;

    private ParameterBagInterface $parameterBag;

    private LoggerInterface $logger;


    public function __construct(
        EntityManagerInterface $entityManager,
        TranslatorInterface    $translator,
        FilesystemService      $filesystemService,
        MessageBusInterface    $messageBus,
        RouterInterface        $router,
        ParameterBagInterface  $parameterBag,
        LoggerInterface        $logger
    )
    {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->filesystemService = $filesystemService;
        $this->messageBus = $messageBus;
        $this->router = $router;
        $this->parameterBag = $parameterBag;
        $this->logger = $logger;
    }

    public function createTemplate(
        User $user
    ): Presentationpage
    {
        $presentationpage = new Presentationpage($user);
        $presentationpage->setType(PresentationpageType::Template);
        $presentationpage->setTitle(
            $this->translator->trans(
                'feature.presentationpages.create_template.new_title',
                ['index' => sizeof($this->getPresentationpagesForUser($user, PresentationpageType::Template)) + 1]
            )
        );
        $presentationpage->setBgColor(BgColor::_FFFFFF);
        $presentationpage->setFgColor(FgColor::_37474F);
        $presentationpage->setTextColor(TextColor::_000000);

        $element = new PresentationpageElement(PresentationpageElementVariant::MercuriusVideo);
        $presentationpage->addPresentationpageElement($element);

        $this->entityManager->persist($element);
        $this->entityManager->persist($presentationpage);
        $this->entityManager->flush();

        return $presentationpage;
    }

    public function createPageFromVideo(
        Video $video,
    ): Presentationpage
    {
        $presentationpage = new Presentationpage($video->getUser());
        $presentationpage->setVideo($video);

        $element = new PresentationpageElement(
            PresentationpageElementVariant::MercuriusVideo
        );

        $presentationpage->addPresentationpageElement($element);

        $presentationpage->setTitle(
            $this->translator->trans(
                'feature.presentationpages.create_page.new_title',
                ['index' => sizeof($this->getPresentationpagesForUser($video->getUser(), PresentationpageType::Page)) + 1]
            )
        );

        $this->entityManager->persist($element);
        $this->entityManager->persist($presentationpage);
        $this->entityManager->flush();

        return $presentationpage;
    }

    /**
     * @throws Exception
     */
    public function createPageFromVideoAndTemplate(
        Video            $video,
        Presentationpage $template
    ): Presentationpage
    {
        if ($video
                ->getUser()
                ->getId()
            !==
            $template
                ->getUser()
                ->getId()
        ) {
            throw new InvalidArgumentException("Video belongs to user '{$video->getUser()->getId()}' while template belongs to user '{$template->getUser()->getId()}'.");
        }

        $presentationpage = new Presentationpage($video->getUser());
        $presentationpage->setTitle(
            $this->translator->trans(
                'feature.presentationpages.create_page.new_title',
                ['index' => sizeof($this->getPresentationpagesForUser($video->getUser(), PresentationpageType::Page)) + 1]
            )
        );
        $presentationpage->setBgColor($template->getBgColor());
        $presentationpage->setTextColor($template->getTextColor());

        foreach ($template->getPresentationpageElements() as $element) {
            $newElement = clone $element;
            $newElement->resetId();
            $newElement->setPresentationpage($presentationpage);
            $presentationpage->addPresentationpageElement($newElement);
            $this->entityManager->persist($newElement);
        }

        $presentationpage->setVideo($video);

        $this->entityManager->persist($presentationpage);
        $this->entityManager->flush();

        return $presentationpage;
    }

    /**
     * @throws Exception
     */
    public function createDraft(Presentationpage $presentationpage): Presentationpage
    {
        if ($presentationpage->isDraft()) {
            throw new Exception("Presentationpage '{$presentationpage->getId()}' is itself a draft, aborting.");
        }

        $draft = new Presentationpage($presentationpage->getUser());
        $draft->setIsDraft(true);
        $draft->setDraftOfPresentationpage($presentationpage);

        $draft->setCreatedAt(DateAndTimeService::getDateTimeUtc());
        $draft->setTitle($presentationpage->getTitle());
        $draft->setType($presentationpage->getType());
        $draft->setBgColor($presentationpage->getBgColor());
        $draft->setTextColor($presentationpage->getTextColor());
        $draft->setFgColor($presentationpage->getFgColor());
        $draft->setBackground($presentationpage->getBackground());
        $draft->setVideo($presentationpage->getVideo());

        foreach ($presentationpage->getPresentationpageElements() as $element) {
            $newElement = clone $element;
            $newElement->resetId();
            $newElement->setPresentationpage($presentationpage);
            $draft->addPresentationpageElement($newElement);
            $this->entityManager->persist($newElement);
        }

        $this->entityManager->persist($draft);
        $this->entityManager->flush();

        return $draft;
    }

    public function handleEdited(Presentationpage $presentationpage): void
    {
        $presentationpage->setUpdatedAt(DateAndTimeService::getDateTimeUtc());
        $this->entityManager->persist($presentationpage);
        $this->entityManager->flush();

        if ($presentationpage->isDraft()) {
            $originalPresentationpage = $presentationpage->getDraftOfPresentationpage();
            if (is_null($originalPresentationpage)) {
                throw new InvalidArgumentException("Expected draft presentationpage '{$presentationpage->getId()}' to provide draftOfPresentationpage, but got null.");
            }

            $originalPresentationpage->setUpdatedAt($presentationpage->getUpdatedAt());
            $originalPresentationpage->setTitle($presentationpage->getTitle());
            $originalPresentationpage->setBgColor($presentationpage->getBgColor());
            $originalPresentationpage->setTextColor($presentationpage->getTextColor());
            $originalPresentationpage->setFgColor($presentationpage->getFgColor());
            $originalPresentationpage->setBackground($presentationpage->getBackground());

            foreach ($originalPresentationpage->getPresentationpageElements() as $element) {
                $originalPresentationpage->removePresentationpageElement($element);
                $this->entityManager->remove($element);
            }
            $this->entityManager->persist($originalPresentationpage);

            foreach ($presentationpage->getPresentationpageElements() as $element) {
                $newElement = clone $element;
                $newElement->resetId();
                $newElement->setPresentationpage($originalPresentationpage);
                $originalPresentationpage->addPresentationpageElement($newElement);
                $this->entityManager->persist($newElement);
            }

            foreach ($presentationpage->getPresentationpageElements() as $element) {
                $presentationpage->removePresentationpageElement($element);
                $this->entityManager->remove($element);
            }
            $this->entityManager->remove($presentationpage);

            $originalPresentationpage->setScreenshotCaptureOutstanding(true);
            $this->entityManager->persist($originalPresentationpage);
            $this->messageBus->dispatch(
                new GeneratePresentationpageScreenshotCommandMessage($originalPresentationpage)
            );

            $this->entityManager->flush();
        }
    }

    /** @return Presentationpage[] */
    public function getPresentationpagesForUser(
        User                     $user,
        PresentationpageType     $type,
        PresentationpageCategory $category = PresentationpageCategory::Default
    ): array
    {
        $results = [];

        /** @var Presentationpage[] $pages */
        $pages = $user->getPresentationpages()
                      ->toArray();
        foreach ($pages as $page) {
            if (    $page->getType() === $type
                &&  $page->getCategory() === $category
                && !$page->isDraft()
            ) {
                $results[] = $page;
            }
        }

        return $results;
    }

    /** @return Presentationpage[] */
    public function getVideoOnlyPresentationpageTemplatesForUser(
        User $user
    ): array
    {
        $templates = $this->getPresentationpagesForUser(
            $user,
            PresentationpageType::Template,
            PresentationpageCategory::VideoOnly
        );

        if (sizeof($templates) === 0) {
            $templates = $this->createBasicSetOfVideoOnlyPresentationpageTemplatesForUser($user);
        }

        return $templates;
    }

    public function userHasTemplates(User $user): bool
    {
        return sizeof(
                $this->getPresentationpagesForUser(
                    $user,
                    PresentationpageType::Template
                )
            ) > 0;
    }

    public function generateScreenshot(Presentationpage $presentationpage): void
    {
        $this->createFilesystemStructuresForScreenshots($presentationpage);

        $targetUrl = $this->router->generate(
            'feature.presentationpages.screenshot_capture_view',
            [
                'presentationpageId' => $presentationpage->getId(),
                'presentationpageHash' => $this->generatePresentationpageHash($presentationpage)
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

        $commandLine = '
            docker run 
                -i
                --init
                --cap-add=SYS_ADMIN
                --rm
                --env TARGET_URL="' . $targetUrl . '"
                --env SCREENSHOT_FILE_PATH="/host/screenshot.webp"
                -v ' . $contentStoragePath . ':/host ghcr.io/puppeteer/puppeteer:latest
                node -e "`cat ' . $this->parameterBag->get('kernel.project_dir') . '/resources/webpage-screenshot-capture/capture.js' . '`"
        ';

        $commandLine = mb_ereg_replace("\n", " ", $commandLine);

        shell_exec($commandLine);

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
        $commandLine = '
            docker run 
                -i
                --init
                --cap-add=SYS_ADMIN
                --rm
                -v ' . $contentStoragePath . ':/host ghcr.io/puppeteer/puppeteer:latest
                rm -f /host/screenshot.webp
        ';

        $commandLine = mb_ereg_replace("\n", " ", $commandLine);

        shell_exec($commandLine);
    }

    public function generatePresentationpageHash(Presentationpage $presentationpage): string
    {
        return sha1('MUI/hdu78764$5uidfrhrfi==478' . $presentationpage->getId());
    }

    private function createFilesystemStructuresForScreenshots(Presentationpage $presentationpage): void
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

    /** @return Presentationpage[] */
    private function createBasicSetOfVideoOnlyPresentationpageTemplatesForUser(
        User $user
    ): array
    {
        $template1 = new Presentationpage($user);
        $template1->setType(PresentationpageType::Template);
        $template1->setCategory(PresentationpageCategory::VideoOnly);
        $template1->setTitle(
            $this
                ->translator
                ->trans(
                    'feature.presentationpages.video_only_presentationpage_template_title.1'
                )
        );
        $template1->setBgColor(BgColor::_FAFAFA);
        $template1->setFgColor(FgColor::_888888);
        $template1->setTextColor(TextColor::_444444);
        $template1->addPresentationpageElement(
            new PresentationpageElement(PresentationpageElementVariant::MercuriusVideo)
        );

        $template2 = new Presentationpage($user);
        $template2->setType(PresentationpageType::Template);
        $template2->setCategory(PresentationpageCategory::VideoOnly);
        $template2->setTitle(
            $this
                ->translator
                ->trans(
                    'feature.presentationpages.video_only_presentationpage_template_title.2'
                )
        );
        $template2->setBgColor(BgColor::_444444);
        $template2->setFgColor(FgColor::_CCCCCC);
        $template2->setTextColor(TextColor::_ECEFF1);
        $template2->addPresentationpageElement(
            new PresentationpageElement(PresentationpageElementVariant::MercuriusVideo)
        );

        $this->entityManager->persist($template1);
        $this->entityManager->persist($template2);
        $this->entityManager->flush();

        return [$template1, $template2];
    }
}
