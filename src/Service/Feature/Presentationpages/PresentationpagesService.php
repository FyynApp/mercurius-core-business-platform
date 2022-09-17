<?php

namespace App\Service\Feature\Presentationpages;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Presentationpages\BgColor;
use App\Entity\Feature\Presentationpages\FgColor;
use App\Entity\Feature\Presentationpages\Presentationpage;
use App\Entity\Feature\Presentationpages\PresentationpageElement;
use App\Entity\Feature\Presentationpages\PresentationpageElementVariant;
use App\Entity\Feature\Presentationpages\PresentationpageType;
use App\Entity\Feature\Presentationpages\TextColor;
use App\Entity\Feature\Recordings\Video;
use App\Service\Aspect\DateAndTime\DateAndTimeService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Symfony\Contracts\Translation\TranslatorInterface;

class PresentationpagesService
{
    private EntityManagerInterface $entityManager;

    private TranslatorInterface $translator;

    public function __construct(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
    ) {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
    }

    public function createPage(
        User $user
    ): Presentationpage {

        $presentationpage = new Presentationpage($user);
        $presentationpage->setTitle(
            $this->translator->trans(
                'feature.presentationpages.create_page.new_title',
                ['index' => sizeof($this->getPresentationpagesForUser($user, PresentationpageType::Page)) + 1]
            )
        );
        $presentationpage->setBgColor(BgColor::_FFFFFF);
        $presentationpage->setFgColor(FgColor::_37474F);
        $presentationpage->setTextColor(TextColor::_000000);

        $element = new PresentationpageElement();
        $element->setElementVariant(PresentationpageElementVariant::MercuriusVideo);
        $element->setPosition(0);

        $presentationpage->addPresentationpageElement($element);

        $this->entityManager->persist($element);
        $this->entityManager->persist($presentationpage);
        $this->entityManager->flush();

        return $presentationpage;
    }


    public function createTemplate(
        User $user
    ): Presentationpage {

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

        $element = new PresentationpageElement();
        $element->setElementVariant(PresentationpageElementVariant::MercuriusVideo);
        $element->setPosition(0);

        $presentationpage->addPresentationpageElement($element);

        $this->entityManager->persist($element);
        $this->entityManager->persist($presentationpage);
        $this->entityManager->flush();

        return $presentationpage;
    }

    /**
     * @throws Exception
     */
    public function createPageFromVideoAndTemplate(
        Video $video,
        Presentationpage $template
    ): Presentationpage {

        if ($video->getUser()->getId() !== $template->getUser()->getId()) {
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

            $this->entityManager->flush();
        }
    }

    /** @return Presentationpage[] */
    public function getPresentationpagesForUser(
        User $user,
        PresentationpageType $type
    ): array
    {
        $results = [];

        /** @var Presentationpage[] $pages */
        $pages = $user->getPresentationpages()->toArray();
        foreach ($pages as $page) {
            if ($page->getType() === $type && !$page->isDraft()) {
                $results[] = $page;
            }
        }

        return $results;
    }

    public function userHasTemplates(User $user): bool
    {
        return sizeof(
            $this->getPresentationpagesForUser(
                $user,
                PresentationpageType::Template
            )) > 0;
    }
}
