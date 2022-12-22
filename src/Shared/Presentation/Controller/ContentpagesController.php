<?php

namespace App\Shared\Presentation\Controller;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class ContentpagesController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/',
        ],
        name        : 'shared.presentation.contentpages.homepage',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function homepageAction(): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (   !is_null($user)
            && $user->isRegistered()
        ) {
            return $this->redirectToRoute('videobasedmarketing.dashboard.presentation.show_registered');
        }

        return $this->redirectToRoute('shared.presentation.contentpages.homepage_extension');
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/extension',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/extension',
        ],
        name        : 'shared.presentation.contentpages.homepage_extension',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function homepageExtensionAction(): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (   !is_null($user)
            && $user->isRegistered()
        ) {
            return $this->redirectToRoute('videobasedmarketing.dashboard.presentation.show_registered');
        }

        return $this->render(
            '@shared/content_pages/homepage_extension.html.twig',
            ['kernel_environment' => $this->getParameter('kernel.environment')]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/features',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/funktionen',
        ],
        name        : 'shared.presentation.contentpages.features',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function featuresAction(): Response
    {
        return $this->render('@shared/content_pages/features.html.twig');
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/pricing',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/preise',
        ],
        name        : 'shared.presentation.contentpages.pricing',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function pricingAction(): Response
    {
        return $this->render('@shared/content_pages/pricing.html.twig');
    }

    #[Route(
        path        : '_content',
        name        : 'shared.presentation.contentpages.wrapped_external_content',
        methods     : [Request::METHOD_GET]
    )]
    public function wrappedExternalContentAction(Request $request): Response
    {
        return $this->render(
            '@shared/content_pages/wrapped_external_content.html.twig',
            ['externalContent' => file_get_contents($request->get('externalContentUrl'))]
        );
    }
}
