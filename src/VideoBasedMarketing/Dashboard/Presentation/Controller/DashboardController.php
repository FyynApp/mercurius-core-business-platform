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
        name        : 'videobasedmarketing.dashboard.presentation.show_registered',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function showRegisteredAction(DashboardService $dashboardService): Response
    {
        return $this->render(
            '@videobasedmarketing.dashboard/show_registered.html.twig',
            [
                'DashboardService' => $dashboardService
            ]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/welcome',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/willkommen',
        ],
        name        : 'videobasedmarketing.dashboard.presentation.show_unregistered',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function showUnregisteredAction(DashboardService $dashboardService): Response
    {
        return $this->render(
            '@videobasedmarketing.dashboard/show_unregistered.html.twig',
            [
                'DashboardService' => $dashboardService
            ]
        );
    }
}
