<?php

namespace App\Controller\Feature\Account;

use App\Entity\Feature\Account\User;
use App\Enum\FlashMessageLabel;
use App\Form\Type\Feature\Account\RegistrationType;
use App\Repository\Feature\Account\UserRepository;
use App\Security\Feature\Account\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    private EmailVerifier $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

    public function registerAction(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!is_null($user)) {
            return $this->redirectToRoute('feature.landingpages.homepage');
        }

        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
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
                    ->htmlTemplate('feature/account/registration/confirmation_email.html.twig')
            );

            return $this->redirectToRoute('feature.landingpages.homepage');
        }

        return $this->render('feature/account/registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    public function verifyEmailAction(Request $request, TranslatorInterface $translator, UserRepository $userRepository, LoginLinkHandlerInterface $loginLinkHandler): Response
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

        return $this->redirect($loginLinkHandler->createLoginLink($user)->getUrl());
    }
}
