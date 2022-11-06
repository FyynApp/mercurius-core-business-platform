<?php

namespace App\VideoBasedMarketing\Dashboard\Presentation\Controller;

use App\VideoBasedMarketing\Dashboard\Domain\Service\DashboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;


class DashboardController
    extends AbstractController
{
    public function showAction(DashboardService $dashboardService): Response
    {
        return $this->render(
            '@videobasedmarketing.dashboard/show.html.twig',
            [
                'DashboardService' => $dashboardService
            ]
        );
    }
}
