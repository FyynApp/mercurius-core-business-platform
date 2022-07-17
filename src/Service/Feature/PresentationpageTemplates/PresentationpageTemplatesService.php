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

    public function addNewTemplate(
        User $user,
        PresentationpageTemplate $template
    ): PresentationpageTemplate {

        $template->setUser($user);

        $this->entityManager->persist($template);
        $this->entityManager->flush($template);

        return $template;
    }
}
