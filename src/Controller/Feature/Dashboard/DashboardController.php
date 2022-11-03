<?php

namespace App\Controller\Feature\Dashboard;

use App\Service\Feature\Dashboard\DashboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;


class DashboardController
    extends AbstractController
{
    public function showAction(DashboardService $dashboardService): Response
    {
        return $this->render(
            'feature/dashboard/show.html.twig',
            [
                'DashboardService' => $dashboardService
            ]
        );
    }
}
