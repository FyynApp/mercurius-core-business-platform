<?php

namespace App\Controller\Feature\PresentationpageTemplates;

use App\Entity\Feature\Account\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class PresentationpageTemplatesController extends AbstractController
{
    public function overviewAction(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render(
            'feature/presentationpage_templates/overview.html.twig',
            ['presentationpageTemplates' => $user->getPresentationpageTemplates()]
        );
    }

    public function addFormAction(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render(
            'feature/presentationpage_templates/add_form.html.twig',
            ['presentationpageTemplates' => $user->getPresentationpageTemplates()]
        );
    }
}
