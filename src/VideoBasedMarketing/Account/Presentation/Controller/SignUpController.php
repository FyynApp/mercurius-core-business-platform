<?php

namespace App\VideoBasedMarketing\Account\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\Shared\Presentation\Enum\FlashMessageLabel;
use App\Shared\Presentation\Service\MailService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Service\AccountDomainService;
use App\VideoBasedMarketing\Account\Infrastructure\Repository\UserRepository;
use App\VideoBasedMarketing\Account\Infrastructure\Security\EmailVerifier;
use App\VideoBasedMarketing\Account\Infrastructure\Service\RequestParametersBasedUserAuthService;
use App\VideoBasedMarketing\Account\Infrastructure\Service\ThirdPartyAuthService;
use App\VideoBasedMarketing\Account\Presentation\Form\Type\SignUpType;
use App\VideoBasedMarketing\Organization\Domain\Service\OrganizationDomainService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;


class SignUpController
    extends AbstractController
{
    public function __construct(
        private readonly EmailVerifier             $emailVerifier,
        private readonly MailService               $mailService,
        private readonly AccountDomainService      $accountDomainService,
        private readonly EntityManagerInterface    $entityManager,
        private readonly TranslatorInterface       $translator,
        private readonly OrganizationDomainService $organizationDomainService
    )
    {
        parent::__construct(
            $this->entityManager,
            $this->organizationDomainService
        );
    }

    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/account/sign-up',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/benutzerkonto/neu-registrieren',
        ],
        name        : 'videobasedmarketing.account.presentation.sign_up',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET, Request::METHOD_POST]
    )]
    public function registerAction(
        Request                     $request,
        ThirdPartyAuthService       $thirdPartyAuthService
    ): Response
    {
        $user = $this->getUser();

        if (   !is_null($user)
            && $user->isRegistered()
        ) {
            return $this->redirectToRoute('shared.presentation.contentpages.homepage');
        }

        $user = new User();
        $form = $this->createForm(SignUpType::class, $user);
        $form->handleRequest($request);

        if (   $form->isSubmitted()
            && !is_null($user->getEmail())
            && $thirdPartyAuthService
                ->userMustBeRedirectedToThirdPartyAuthLinkedinEndpoint(
                    $user->getEmail()
                )
        ) {
            return $this->redirectToRoute('videobasedmarketing.account.infrastructure.thirdpartyauth.linkedin.start');
        }

        if ($form->isSubmitted() && $form->isValid()) {

            $this->accountDomainService->createRegisteredUser(
                $form->get('email')->getData(),
                $form->get('plainPassword')->getData(),
                false,
                $user
            );

            $this->emailVerifier->sendEmailAskingForVerification(
                'videobasedmarketing.account.presentation.sign_up.email_verification',
                $user,
                (new TemplatedEmail())
                    ->from($this->mailService->getDefaultSenderAddress())
                    ->to($user->getEmail())
                    ->subject('Please confirm your email')
                    ->htmlTemplate('@videobasedmarketing.account/sign_up/confirmation_email.html.twig')
            );

            return $this->redirectToRoute(
                'videobasedmarketing.account.presentation.sign_up.pls_check_your_email',
                ['email' => $user->getEmail()]
            );
        }

        return $this->render(
            '@videobasedmarketing.account/sign_up/form.html.twig',
            [
                'signUpForm' => $form->createView(),
            ]
        );
    }


    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/account/sign-up/please-check-your-email',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/benutzerkonto/neu-registrieren/bitte-ueberpruefe-dein-email-postfach',
        ],
        name        : 'videobasedmarketing.account.presentation.sign_up.pls_check_your_email',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET]
    )]
    public function verficationEmailNoteAction(
        Request $request
    ): Response
    {
        return $this->render(
            '@videobasedmarketing.account/sign_up/please_verify_email_address.html.twig',
            ['email' => $request->get('email')]
        );
    }


    #[Route(
        path        : [
            'en' => '%app.routing.route_prefix.with_locale.unprotected.en%/account/sign-up/email-verification',
            'de' => '%app.routing.route_prefix.with_locale.unprotected.de%/benutzerkonto/neu-registrieren/email-verifikation',
        ],
        name        : 'videobasedmarketing.account.presentation.sign_up.email_verification',
        requirements: ['_locale' => '%app.routing.locale_requirement%'],
        methods     : [Request::METHOD_GET, Request::METHOD_POST]
    )]
    public function verifyEmailAction(
        Request                               $request,
        TranslatorInterface                   $translator,
        UserRepository                        $userRepository,
        RequestParametersBasedUserAuthService $requestParametersBasedUserAuthService
    ): Response
    {
        $id = $request->get('id');

        if (null === $id) {
            return $this->redirectToRoute('videobasedmarketing.account.presentation.sign_up');
        }

        /** @var User $user */
        $user = $userRepository->find($id);

        if (null === $user) {
            return $this->redirectToRoute('videobasedmarketing.account.presentation.sign_up');
        }

        try {
            $this->accountDomainService->handleVerificationRequest($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('videobasedmarketing.account.presentation.sign_up');
        }

        $this->addFlash(
            FlashMessageLabel::Success->value,
            $this->translator->trans(
                'email_verification.email_has_been_verified',
                [],
                'videobasedmarketing.account'
            )
        );

        return $requestParametersBasedUserAuthService->createRedirectResponse(
            $user,
            'shared.presentation.contentpages.homepage'
        );
    }
}
