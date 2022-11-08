<?php

namespace App\VideoBasedMarketing\Dashboard\Presentation\Controller;

use App\VideoBasedMarketing\Dashboard\Domain\Service\DashboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class DashboardController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/dashboard',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/übersicht',
        ],
        name        : 'videobasedmarketing.dashboard.presentation.show',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
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
