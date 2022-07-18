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
    public function overviewAction(PresentationpageTemplatesService $presentationpageTemplatesService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render(
            'feature/presentationpage_templates/overview.html.twig',
            ['PresentationpageTemplatesService' => $presentationpageTemplatesService]
        );
    }

    public function addFormAction(
        Request $request,
        PresentationpageTemplatesService $presentationpageTemplatesService
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $template = new PresentationpageTemplate();

        $form = $this->createForm(PresentationpageTemplateType::class, $template);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var PresentationpageTemplate $template */
            $template = $form->getData();

            $presentationpageTemplatesService->addNewTemplate($user, $template);

            return $this->redirectToRoute('feature.presentationpage_templates.overview');
        } else {
            return $this->renderForm(
                'feature/presentationpage_templates/add_form.html.twig',
                [
                    'form' => $form,
                    'PresentationpageTemplatesService' => $presentationpageTemplatesService
                ]
            );
        }
    }
}
