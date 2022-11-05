<?php

namespace App\VideoBasedMarketing\Account\Presentation\Controller;

use App\Enum\FlashMessageLabel;
use App\Security\Feature\Account\EmailVerifier;
use App\VideoBasedMarketing\Account\Domain\Entity\Role;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Infrastructure\Repository\UserRepository;
use App\VideoBasedMarketing\Account\Infrastructure\Service\ThirdPartyAuthService;
use App\VideoBasedMarketing\Account\Presentation\Form\Type\SignUpType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;


class SignUpController
    extends AbstractController
{
    private EmailVerifier $emailVerifier;


    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

    #[Route(
        path        : [
            'en' => '{_locale}/account/sign-up',
            'de' => '{_locale}/benutzerkonto/neu-registrieren',
        ],
        name        : 'videobasedmarketing.account.sign_up',
        requirements: ['_locale' => '%app.route_locale_requirement%'],
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

        if (!is_null($user)) {
            return $this->redirectToRoute('feature.landingpages.homepage');
        }

        $user = new User();
        $form = $this->createForm(SignUpType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()
            && !is_null($user->getEmail())
            && $thirdPartyAuthService
                ->userMustBeRedirectedToThirdPartyAuthLinkedinEndpoint(
                    $user->getEmail()
                )
        ) {
            return $this->redirectToRoute('videobasedmarketing.account.thirdpartyauth.linkedin.start');
        }

        if ($form->isSubmitted() && $form->isValid()) {

            $user->addRole(Role::REGISTERED_USER);

            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')
                         ->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            $this->emailVerifier->sendEmailConfirmation(
                'feature.account.verify_email',
                $user,
                (new TemplatedEmail())
                    ->from(new Address('no-reply@fyyn.io', 'Fyyn.io'))
                    ->to($user->getEmail())
                    ->subject('Please confirm your email')
                    ->htmlTemplate('feature/account/sign_up/confirmation_email.html.twig')
            );

            return $this->redirectToRoute('feature.landingpages.homepage');
        }

        return $this->render(
            '@videobasedmarketing.account/sign_up/form.html.twig',
            [
                'signUpForm' => $form->createView(),
            ]
        );
    }


    public function verifyEmailAction(
        Request                   $request,
        TranslatorInterface       $translator,
        UserRepository            $userRepository,
        LoginLinkHandlerInterface $loginLinkHandler
    ): Response
    {
        $id = $request->get('id');

        if (null === $id) {
            return $this->redirectToRoute('feature.account.register');
        }

        $user = $userRepository->find($id);

        if (null === $user) {
            return $this->redirectToRoute('feature.account.register');
        }

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('feature.account.register');
        }

        $this->addFlash(FlashMessageLabel::Success->value, 'Your email address has been verified.');

        return $this->redirect(
            $loginLinkHandler
                ->createLoginLink($user)
                ->getUrl()
        );
    }
}
