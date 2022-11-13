<?php

namespace App\VideoBasedMarketing\Account\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class ClaimUnregisteredUserController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.protected.en%/account/claim',
            'de' => '%app.routing.route_prefix.with_locale.protected.de%/benutzerkonto/beanspruchen',
        ],
        name        : 'videobasedmarketing.account.presentation.claim_unregistered_user_landinpage',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function indexAction(): Response
    {
        $user = $this->getUser();

        if ($user->isRegistered()) {
            $this->redirectToRoute(
                'videobasedmarketing.dashboard.presentation.show_registered'
            );
        }

        return $this->render(
            '@videobasedmarketing.account/claim_unregistered_user/form.html.twig',
            []
        );
    }
}
