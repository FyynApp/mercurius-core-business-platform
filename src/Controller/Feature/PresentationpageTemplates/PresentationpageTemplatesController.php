<?php

namespace App\Controller\Feature\PresentationpageTemplates;

use App\Entity\Feature\Account\User;
use App\Entity\Feature\PresentationpageTemplates\PresentationpageTemplate;
use App\Form\Type\Feature\PresentationpageTemplates\PresentationpageTemplateType;
use App\Service\Feature\PresentationpageTemplates\PresentationpageTemplatesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PresentationpageTemplatesController extends AbstractController
{
    public function overviewAction(PresentationpageTemplatesService $presentationpageTemplatesService): Response
    {
        return $this->render(
            'feature/presentationpage_templates/overview.html.twig',
            ['PresentationpageTemplatesService' => $presentationpageTemplatesService]
        );
    }

    public function createAction(
        PresentationpageTemplatesService $presentationpageTemplatesService
    ): Response
    {
        $template = $presentationpageTemplatesService->createTemplate($this->getUser());

        return $this->redirectToRoute(
            'feature.presentationpage_templates.editor',
            ['presentationpageTemplateId' => $template->getId()]
        );
    }

    public function editorAction(
        string                           $presentationpageTemplateId,
        Request                          $request,
        EntityManagerInterface           $entityManager,
        PresentationpageTemplatesService $presentationpageTemplatesService
    ): Response
    {
        $template = $entityManager->find(PresentationpageTemplate::class, $presentationpageTemplateId);

        if (is_null($template)) {
            throw new NotFoundHttpException("No template with id '$presentationpageTemplateId'.");
        }

        /** @var User $user */
        $user = $this->getUser();

        if ($user->getId() !== $template->getUser()->getId()) {
            throw new AccessDeniedHttpException("The template with id '{$template->getId()}' does not belong to the current user.");
        }

        $form = $this->createForm(PresentationpageTemplateType::class, $template);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var PresentationpageTemplate $template */
            $template = $form->getData();

            $entityManager->persist($template);
            $entityManager->flush();

            return $this->redirectToRoute('feature.presentationpage_templates.overview');
        } else {
            return $this->renderForm(
                'feature/presentationpage_templates/editor.html.twig',
                [
                    'presentationpageTemplate' => $template,
                    'form' => $form,
                    'PresentationpageTemplatesService' => $presentationpageTemplatesService
                ]
            );
        }
    }

    public function previewAction(
        string                 $presentationpageTemplateId,
        EntityManagerInterface $entityManager
    ): Response
    {
        $presentationpageTemplate = $entityManager->find(PresentationpageTemplate::class, $presentationpageTemplateId);

        if (is_null($presentationpageTemplate)) {
            throw new NotFoundHttpException("No presentationpage template with id '$presentationpageTemplateId' found.");
        }

        return $this->render(
            'feature/presentationpage_templates/preview.html.twig',
            ['presentationpageTemplate' => $presentationpageTemplate]
        );
    }
}
