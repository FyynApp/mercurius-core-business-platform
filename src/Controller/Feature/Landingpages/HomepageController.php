<?php

namespace App\Controller\Feature\Landingpages;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class HomepageController extends AbstractController
{
    public function indexAction(): Response
    {
        return $this->render('feature/landingpages/homepage.html.twig');
    }
}
