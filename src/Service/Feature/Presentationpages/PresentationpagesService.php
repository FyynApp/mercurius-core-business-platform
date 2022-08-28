<?php

namespace App\Service\Feature\Presentationpages;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\Presentationpages\Presentationpage;
use App\Entity\Feature\Presentationpages\PresentationpageElement;
use App\Entity\Feature\Presentationpages\PresentationpageElementVariant;
use App\Entity\Feature\Recordings\Video;
use App\Service\Aspect\DateAndTime\DateAndTimeService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Contracts\Translation\TranslatorInterface;

class PresentationpagesService
{
    private EntityManagerInterface $entityManager;

    private TranslatorInterface $translator;

    public function __construct(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator
    ) {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
    }

    public function createDefaultTemplate(
        User $user
    ): Presentationpage {

        $presentationpage = new Presentationpage();
        $presentationpage->setIsTemplate(true);
        $presentationpage->setTitle($this->translator->trans('feature.presentationpages.create.default_title'));
        $presentationpage->setBgColor(Presentationpage::ALLOWED_BG_COLORS[0]);
        $presentationpage->setTextColor(Presentationpage::ALLOWED_TEXT_COLORS[0]);
        $presentationpage->setUser($user);
        $this->entityManager->persist($presentationpage);
        $this->entityManager->flush();

        return $presentationpage;
    }


    public function createTemplate(
        User $user
    ): Presentationpage {

        $presentationpage = new Presentationpage();
        $presentationpage->setIsTemplate(true);
        $presentationpage->setTitle(
            $this->translator->trans(
                'feature.presentationpages.create.new_title',
                ['index' => sizeof($this->getPresentationpagesForUser($user)) + 1]
            )
        );
        $presentationpage->setBgColor(Presentationpage::ALLOWED_BG_COLORS[0]);
        $presentationpage->setTextColor(Presentationpage::ALLOWED_TEXT_COLORS[0]);
        $presentationpage->setUser($user);

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
    public function createFromVideo(
        Video $video
    ): Presentationpage {

        $presentationpage = new Presentationpage();
        $presentationpage->setTitle(
            $this->translator->trans(
                'feature.presentationpages.create.new_title',
                ['index' => sizeof($this->getPresentationpagesForUser($video->getUser())) + 1]
            )
        );
        $presentationpage->setBgColor(Presentationpage::ALLOWED_BG_COLORS[0]);
        $presentationpage->setTextColor(Presentationpage::ALLOWED_TEXT_COLORS[0]);
        $presentationpage->setUser($video->getUser());

        $element = new PresentationpageElement();
        $element->setElementVariant(PresentationpageElementVariant::MercuriusVideo);
        $element->setPosition(0);

        $presentationpage->addPresentationpageElement($element);

        $presentationpage->setVideo($video);

        $this->entityManager->persist($element);
        $this->entityManager->persist($presentationpage);
        $this->entityManager->flush();

        return $presentationpage;
    }

    /** @return Presentationpage[] */
    public function getPresentationpagesForUser(User $user): array
    {
        return $user->getPresentationpages()->toArray();
    }


    /** @return string[] */
    public function getAvailableBgColors(): array
    {
        return Presentationpage::ALLOWED_BG_COLORS;
    }

    /** @return string[] */
    public function getAvailableTextColors(): array
    {
        return Presentationpage::ALLOWED_TEXT_COLORS;
    }
}
