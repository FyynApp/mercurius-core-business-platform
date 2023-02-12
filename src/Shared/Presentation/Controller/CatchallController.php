<?php

namespace App\Shared\Presentation\Controller;

use App\Shared\Domain\Service\Iso639_1CodeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;


class CatchallController
    extends AbstractController
{
    public function indexAction(
        RouterInterface $router,
        Request         $request
    ): Response
    {
        return new RedirectResponse(
            $router->generate(
                'shared.presentation.contentpages.homepage',
                ['_locale' => Iso639_1CodeService::getCodeFromRequest($request)->value]
            )
        );
    }
}
