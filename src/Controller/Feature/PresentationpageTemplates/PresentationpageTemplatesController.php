<?php

namespace App\Controller\Feature\PresentationpageTemplates;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\PresentationpageTemplates\PresentationpageTemplate;
use App\Form\Type\Feature\PresentationpageTemplates\PresentationpageTemplateType;
use App\Service\Feature\PresentationpageTemplates\PresentationpageTemplatesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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

    public function addFormAction(Request $request, PresentationpageTemplatesService $templatesService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $template = new PresentationpageTemplate();

        $form = $this->createForm(PresentationpageTemplateType::class, $template);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var PresentationpageTemplate $template */
            $template = $form->getData();

            $templatesService->addNewTemplate($user, $template);

            return $this->redirectToRoute('feature.presentationpage_templates.overview');
        } else {
            return $this->renderForm(
                'feature/presentationpage_templates/add_form.html.twig',
                [
                    'form' => $form,
                    'bgColors' => PresentationpageTemplate::ALLOWED_BG_COLORS,
                    'textColors' => PresentationpageTemplate::ALLOWED_TEXT_COLORS
                ]
            );
        }
    }
}
