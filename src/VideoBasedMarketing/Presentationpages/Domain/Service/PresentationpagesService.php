<?php

namespace App\VideoBasedMarketing\Presentationpages\Domain\Service;

use App\Shared\Infrastructure\Service\DateAndTimeService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Presentationpages\Domain\Entity\Presentationpage;
use App\VideoBasedMarketing\Presentationpages\Domain\Entity\PresentationpageElement;
use App\VideoBasedMarketing\Presentationpages\Domain\Enum\BgColor;
use App\VideoBasedMarketing\Presentationpages\Domain\Enum\FgColor;
use App\VideoBasedMarketing\Presentationpages\Domain\Enum\PresentationpageCategory;
use App\VideoBasedMarketing\Presentationpages\Domain\Enum\PresentationpageElementVariant;
use App\VideoBasedMarketing\Presentationpages\Domain\Enum\PresentationpageType;
use App\VideoBasedMarketing\Presentationpages\Domain\Enum\TextColor;
use App\VideoBasedMarketing\Presentationpages\Infrastructure\SymfonyMessage\GeneratePresentationpageScreenshotCommandSymfonyMessage;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


class PresentationpagesService
{
    private EntityManagerInterface $entityManager;

    private TranslatorInterface $translator;

    private MessageBusInterface $messageBus;


    public function __construct(
        EntityManagerInterface $entityManager,
        TranslatorInterface    $translator,
        MessageBusInterface    $messageBus
    )
    {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->messageBus = $messageBus;
    }

    public function createTemplate(
        User $user
    ): Presentationpage
    {
        $presentationpage = new Presentationpage($user);
        $presentationpage->setType(PresentationpageType::Template);
        $presentationpage->setTitle(
            $this->translator->trans(
                'create_template.new_title',
                ['index' => sizeof($this->getPresentationpagesForUser($user, PresentationpageType::Template)) + 1],
                'videobasedmarketing.presentationpages'
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
                'create_page.new_title',
                ['index' => sizeof($this->getPresentationpagesForUser($video->getUser(), PresentationpageType::Page)) + 1],
                'videobasedmarketing.presentationpages'
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
                'create_page.new_title',
                ['index' => sizeof($this->getPresentationpagesForUser($video->getUser(), PresentationpageType::Page)) + 1],
                'videobasedmarketing.presentationpages'
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

        $draft->setCreatedAt(DateAndTimeService::getDateTime());
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

    /**
     * @throws Exception
     */
    public function handleEdited(Presentationpage $presentationpage): void
    {
        $presentationpage->setUpdatedAt(DateAndTimeService::getDateTime());
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
                new GeneratePresentationpageScreenshotCommandSymfonyMessage($originalPresentationpage)
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
            $templates = $this
                ->createBasicSetOfVideoOnlyPresentationpageTemplatesForUser(
                    $user
                );
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

    public function generatePresentationpageHash(Presentationpage $presentationpage): string
    {
        return sha1('MUI/hdu78764$5uidfrhrfi==478' . $presentationpage->getId());
    }

    public function createBasicSetOfVideoOnlyPresentationpageTemplatesForUserIfNotExist(
        User $user
    ): void
    {
        $templates = $this->getPresentationpagesForUser(
            $user,
            PresentationpageType::Template,
            PresentationpageCategory::VideoOnly
        );

        if (sizeof($templates) === 0) {
            $templates = $this
                ->createBasicSetOfVideoOnlyPresentationpageTemplatesForUser(
                    $user
                );
        }
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
                    'video_only_presentationpage_template_title.1',
                    [],
                    'videobasedmarketing.presentationpages'
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
                    'video_only_presentationpage_template_title.2',
                    [],
                    'videobasedmarketing.presentationpages'
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
