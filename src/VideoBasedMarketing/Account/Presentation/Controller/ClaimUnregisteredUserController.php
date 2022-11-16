<?php

namespace App\VideoBasedMarketing\Account\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Service\UserDomainService;
use App\VideoBasedMarketing\Account\Infrastructure\Service\RequestParametersBasedUserAuthService;
use App\VideoBasedMarketing\Account\Presentation\Form\Type\ClaimUnregisteredUserType;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoDomainService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class ClaimUnregisteredUserController
    extends AbstractController
{
    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/account/claim',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/benutzerkonto/beanspruchen',
        ],
        name        : 'videobasedmarketing.account.presentation.claim_unregistered_user.landingpage',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function indexAction(
        VideoDomainService $videoDomainService
    ): Response
    {
        $user = $this->getUser();

        if ($user->isRegistered()) {
            return $this->redirectToRoute(
                'videobasedmarketing.dashboard.presentation.show_registered'
            );
        }

        $form = $this->createForm(ClaimUnregisteredUserType::class);

        return $this->render(
            '@videobasedmarketing.account/claim_unregistered_user/landingpage.html.twig',
            [
                'videos' => $videoDomainService->getAvailableVideos($user),
                'form' => $form->createView()
            ]
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/account/claim/handle',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/benutzerkonto/beanspruchen/verarbeiten',
        ],
        name        : 'videobasedmarketing.account.presentation.claim_unregistered_user.handle_form_submit',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_POST]
    )]
    public function handleFormSubmitAction(
        Request                               $request,
        UserDomainService                     $userService,
        RequestParametersBasedUserAuthService $requestParametersBasedUserAuthService
    ): Response
    {
        $user = $this->getUser();

        if (is_null($user)) {
            throw $this->createAccessDeniedException('No user.');
        }

        if ($user->isRegistered()) {
            throw $this->createAccessDeniedException("Only unregistered users allowed, but '{$user->getUserIdentifier()}' is registered.");
        }

        $form = $this->createForm(ClaimUnregisteredUserType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $success = $userService->handleUnregisteredUserClaim(
                $user,
                $form->getData()['email']
            );

            if ($success) {
                return $requestParametersBasedUserAuthService
                    ->createRedirectResponse(
                        'videobasedmarketing.dashboard.presentation.show_registered',
                        $user
                    );
            } else {
                return new Response('', Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return new Response('', Response::HTTP_NOT_IMPLEMENTED);
    }
}
