<?php

namespace App\Controller\Feature\Landingpages;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class LandingpagesController extends AbstractController
{
    public function homepageAction(): Response
    {
        if (!is_null($this->getUser())) {
            return $this->redirectToRoute('feature.dashboard.show');
        }
        return $this->render('feature/landingpages/homepage.html.twig');
    }

    public function featuresAction(): Response
    {
        return $this->render('feature/landingpages/features.html.twig');
    }

    public function pricingAction(): Response
    {
        return $this->render('feature/landingpages/pricing.html.twig');
    }
}
