<?php

namespace App\Controller\Feature\Dashboard;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends AbstractController
{
    public function showAction(): Response
    {

        return $this->render('feature/dashboard/show.html.twig');
    }
}
