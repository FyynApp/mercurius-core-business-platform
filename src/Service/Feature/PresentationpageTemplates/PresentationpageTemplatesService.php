<?php

namespace App\Service\Feature\PresentationpageTemplates;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\PresentationpageTemplates\PresentationpageTemplate;
use Doctrine\ORM\EntityManagerInterface;

class PresentationpageTemplatesService
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public function createDefaultTemplate(
        User $user
    ): PresentationpageTemplate {

        $template = new PresentationpageTemplate();
        $template->setTitle('Default');
        $template->setBgColor(PresentationpageTemplate::ALLOWED_BG_COLORS[0]);
        $template->setTextColor(PresentationpageTemplate::ALLOWED_TEXT_COLORS[0]);
        $template->setUser($user);
        $this->entityManager->persist($template);
        $this->entityManager->flush($template);

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
