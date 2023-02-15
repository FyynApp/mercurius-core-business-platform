<?php

namespace App\VideoBasedMarketing\Account\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Service\AccountDomainService;
use App\VideoBasedMarketing\Account\Infrastructure\Service\RequestParametersBasedUserAuthService;
use App\VideoBasedMarketing\Account\Presentation\Form\Type\ClaimUnregisteredUserType;
use App\VideoBasedMarketing\Recordings\Domain\Service\VideoDomainService;
use Doctrine\ORM\EntityManagerInterface;
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
    public function landingpageAction(
        VideoDomainService $videoDomainService
    ): Response
    {
        $user = $this->getUser();

        if (is_null($user)) {
            return $this->redirectToRoute(
                'shared.presentation.contentpages.homepage'
            );
        }

        if ($user->isRegistered()) {
            if ($user->isVerified()) {
                return $this->redirectToRoute(
                    'videobasedmarketing.dashboard.presentation.show_registered'
                );
            } else {
                return $this->redirectToRoute(
                    'videobasedmarketing.account.presentation.claim_unregistered_user.please_verify_email_address'
                );
            }
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
        Request                $request,
        AccountDomainService   $userService,
        EntityManagerInterface $entityManager,
        VideoDomainService     $videoDomainService
    ): Response
    {
        $user = $this->getUser();

        if (is_null($user)) {
            throw $this->createAccessDeniedException('No user.');
        }

        if ($user->isRegistered()) {
            throw $this->createAccessDeniedException(
                "Only unregistered users allowed, but '{$user->getUserIdentifier()}' is registered."
            );
        }

        $form = $this->createForm(ClaimUnregisteredUserType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $existingUser = $entityManager
                ->getRepository(User::class)
                ->findOneBy([
                    'email' => $form->getData()['email']
                ]);
            if (!is_null($existingUser)) {

                if ($existingUser->isVerified()) {
                    return $this->redirectToRoute(
                        'videobasedmarketing.account.presentation.sign_in',
                        ['username' => $existingUser->getEmail()]
                    );
                }

                $userService->handleUnregisteredUserReclaimsEmail(
                    $existingUser
                );

                return $this->redirectToRoute(
                    'videobasedmarketing.account.presentation.claim_unregistered_user.please_verify_email_address',
                    ['email' => $existingUser->getEmail()]
                );
            }

            $success = $userService->handleUnregisteredUserClaimsEmail(
                $user,
                $form->getData()['email'],
                $form->getData()['plainPassword']
            );

            if ($success) {
                return $this->redirectToRoute(
                    'videobasedmarketing.account.presentation.claim_unregistered_user.please_verify_email_address',
                    ['email' => $user->getEmail()]
                );
            } else {
                return new Response('', Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return $this->render(
            '@videobasedmarketing.account/claim_unregistered_user/landingpage.html.twig',
            [
                'videos' => $videoDomainService->getAvailableVideos($user),
                'form' => $form->createView()
            ],
            new Response(null, Response::HTTP_BAD_REQUEST)
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/account/claim/please-verify-email-address',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/benutzerkonto/beanspruchen/bitte-email-adresse-bestätigen',
        ],
        name        : 'videobasedmarketing.account.presentation.claim_unregistered_user.please_verify_email_address',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function showPleaseVerifyEmailAddressAction(
        Request $request
    ): Response
    {
        /** @var null|User $user */
        $user = $this->getUser();

        if (!is_null($user) && $user->isVerified()) {
            return $this->redirectToRoute(
                'videobasedmarketing.dashboard.presentation.show_registered'
            );
        }
        $email = (string)$request->get('email');

        return $this->render(
            '@videobasedmarketing.account/claim_unregistered_user/please_verify_email_address.html.twig',
            ['email' => $email]
        );
    }
}
