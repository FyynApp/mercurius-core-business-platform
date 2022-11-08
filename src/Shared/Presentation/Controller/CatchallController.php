<?php

namespace App\Shared\Presentation\Controller;

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
        $preferredLanguage = $request->getPreferredLanguage();

        if ($preferredLanguage === 'de'
            || mb_substr($preferredLanguage, 0, 3) === 'de_'
            || mb_substr($preferredLanguage, 0, 3) === 'de-'
        ) {
            return new RedirectResponse(
                $router->generate(
                    'shared.presentation.contentpages.homepage',
                    ['_locale' => 'de']
                )
            );
        } else {
            return new RedirectResponse(
                $router->generate(
                    'shared.presentation.contentpages.homepage',
                    ['_locale' => 'en']
                )
            );
        }
    }
}
