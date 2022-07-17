<?php

namespace App\Controller\Feature\PresentationpageTemplates;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\PresentationpageTemplates\PresentationpageTemplate;
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
            [
                'bgColors' => PresentationpageTemplate::ALLOWED_BG_COLORS,
                'textColors' => PresentationpageTemplate::ALLOWED_TEXT_COLORS,
            ]
        );
    }


    public function addAction(): Response
    {
        /** @var User $user */
        $user = $this->getUser();



        return $this->redirectToRoute('feature.presentationpage_templates.overview');
    }
}
