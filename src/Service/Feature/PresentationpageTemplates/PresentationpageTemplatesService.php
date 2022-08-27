<?php

namespace App\Service\Feature\PresentationpageTemplates;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\PresentationpageTemplates\PresentationpageTemplate;
use App\Entity\Feature\PresentationpageTemplates\PresentationpageTemplateElement;
use App\Entity\Feature\PresentationpageTemplates\PresentationpageTemplateElementVariant;
use App\Service\Aspect\DateAndTime\DateAndTimeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PresentationpageTemplatesService
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
    ): PresentationpageTemplate {

        $template = new PresentationpageTemplate();
        $template->setTitle($this->translator->trans('feature.presentationpage_templates.create.default_title'));
        $template->setBgColor(PresentationpageTemplate::ALLOWED_BG_COLORS[0]);
        $template->setTextColor(PresentationpageTemplate::ALLOWED_TEXT_COLORS[0]);
        $template->setUser($user);
        $this->entityManager->persist($template);
        $this->entityManager->flush();

        return $template;
    }


    public function createTemplate(
        User $user
    ): PresentationpageTemplate {

        $template = new PresentationpageTemplate();
        $template->setTitle(
            $this->translator->trans(
                'feature.presentationpage_templates.create.new_title',
                ['index' => sizeof($this->getTemplatesForUser($user)) + 1]
            )
        );
        $template->setBgColor(PresentationpageTemplate::ALLOWED_BG_COLORS[0]);
        $template->setTextColor(PresentationpageTemplate::ALLOWED_TEXT_COLORS[0]);
        $template->setUser($user);

        $element = new PresentationpageTemplateElement();
        $element->setElementVariant(PresentationpageTemplateElementVariant::MercuriusVideo);
        $element->setPosition(0);

        $template->addPresentationpageTemplateElement($element);

        $this->entityManager->persist($element);
        $this->entityManager->persist($template);
        $this->entityManager->flush();

        return $template;
    }


    /** @return PresentationpageTemplate[] */
    public function getTemplatesForUser(User $user): array
    {
        return $user->getPresentationpageTemplates()->toArray();
    }


    /** @return string[] */
    public function getAvailableBgColors(): array
    {
        return PresentationpageTemplate::ALLOWED_BG_COLORS;
    }

    /** @return string[] */
    public function getAvailableTextColors(): array
    {
        return PresentationpageTemplate::ALLOWED_TEXT_COLORS;
    }
}
