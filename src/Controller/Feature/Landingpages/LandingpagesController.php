<?php

namespace App\Controller\Feature\Landingpages;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LandingpagesController extends AbstractController
{
    public function homepageAction(): Response
    {
        if (!is_null($this->getUser())) {
            return $this->redirectToRoute('feature.dashboard.show');
        }
        return $this->render(
            'feature/landingpages/homepage.html.twig',
            ['kernel_environment' => $this->getParameter('kernel.environment')]
        );
    }

    public function featuresAction(): Response
    {
        return $this->render('feature/landingpages/features.html.twig');
    }

    public function pricingAction(): Response
    {
        return $this->render('feature/landingpages/pricing.html.twig');
    }

    public function wrappedExternalContentAction(Request $request): Response
    {
        return $this->render(
            'feature/landingpages/wrapped_external_content.html.twig',
            ['externalContent' => file_get_contents($request->get('externalContentUrl'))]
        );
    }
}
