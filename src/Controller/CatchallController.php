<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class CatchallController extends AbstractController
{
    public function indexAction(RouterInterface $router): Response
    {
        return new RedirectResponse($router->generate('feature.landingpages.homepage'));
    }
}
