<?php

namespace App\VideoBasedMarketing\Account\Presentation\Controller;

use App\Shared\Infrastructure\Controller\AbstractController;
use App\Shared\Presentation\Enum\FlashMessageLabel;
use App\Shared\Presentation\Service\MailService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Enum\Role;
use App\VideoBasedMarketing\Account\Domain\Service\AccountDomainService;
use App\VideoBasedMarketing\Account\Infrastructure\Repository\UserRepository;
use App\VideoBasedMarketing\Account\Infrastructure\Security\EmailVerifier;
use App\VideoBasedMarketing\Account\Infrastructure\Service\RequestParametersBasedUserAuthService;
use App\VideoBasedMarketing\Account\Infrastructure\Service\ThirdPartyAuthService;
use App\VideoBasedMarketing\Account\Presentation\Form\Type\SignUpType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;


class SignUpController
    extends AbstractController
{
    private EmailVerifier $emailVerifier;

    private MailService $mailService;

    private AccountDomainService $accountDomainService;


    public function __construct(
        EmailVerifier          $emailVerifier,
        MailService            $mailService,
        EntityManagerInterface $entityManager,
        AccountDomainService   $accountDomainService
    )
    {
        $this->emailVerifier = $emailVerifier;
        $this->mailService = $mailService;
        $this->accountDomainService = $accountDomainService;
        parent::__construct($entityManager);
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
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface      $entityManager,
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

            $user->addRole(Role::REGISTERED_USER);
            $user->addRole(Role::EXTENSION_ONLY_USER);

            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')
                         ->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

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

        $this->addFlash(FlashMessageLabel::Success->value, 'Your email address has been verified.');

        return $requestParametersBasedUserAuthService->createRedirectResponse(
            $user,
            'shared.presentation.contentpages.homepage'
        );
    }
}
