<?php

namespace App\VideoBasedMarketing\Account\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Enum\Role;
use App\VideoBasedMarketing\Account\Domain\Enum\VideosListViewMode;
use App\VideoBasedMarketing\Account\Domain\SymfonyEvent\UnregisteredUserClaimedRegisteredUserSymfonyEvent;
use App\VideoBasedMarketing\Account\Domain\SymfonyEvent\UserCreatedSymfonyEvent;
use App\VideoBasedMarketing\Account\Infrastructure\Enum\ActiveCampaignContactTag;
use App\VideoBasedMarketing\Account\Infrastructure\SymfonyEvent\UserVerifiedSymfonyEvent;
use App\VideoBasedMarketing\Account\Infrastructure\SymfonyMessage\SyncUserToActiveCampaignCommandSymfonyMessage;
use App\VideoBasedMarketing\Account\Presentation\Service\AccountPresentationService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use ValueError;


readonly class AccountDomainService
{


    public function __construct(
        private EntityManagerInterface      $entityManager,
        private AccountPresentationService  $presentationService,
        private MessageBusInterface         $messageBus,
        private VerifyEmailHelperInterface  $verifyEmailHelper,
        private EventDispatcherInterface    $eventDispatcher,
        private UserPasswordHasherInterface $userPasswordHasher
    )
    {
    }


    /**
     * @throws Exception
     */
    public function createRegisteredUser(
        string  $email,
        ?string $plainPassword = null,
        bool    $isVerified = false,
        ?User   $user = null
    ): User
    {
        $email = trim(mb_strtolower($email));
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(
            ['email' => $email]
        );

        if (!is_null($existingUser)) {
            throw new ValueError("User with email '$email' already exists.");
        }

        if (is_null($user)) {
            $user = new User();
        }

        $user->setEmail($email);

        if (is_null($plainPassword)) {
            $plainPassword = random_int(PHP_INT_MIN, PHP_INT_MAX);
        }

        $user->addRole(Role::REGISTERED_USER);
        $user->addRole(Role::EXTENSION_ONLY_USER);

        $user->setPassword(
            $this->userPasswordHasher->hashPassword(
                $user,
                $plainPassword
            )
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(
            new UserCreatedSymfonyEvent($user)
        );

        if ($isVerified) {
            $this->makeUserVerified($user);
        }

        return $user;
    }

    /**
     * @throws Exception
     */
    public function createUnregisteredUser(
        bool $asExtensionOnlyUser = false
    ): User
    {
        $user = new User();
        $user->setEmail(
            sha1(
                'fh45897z784787h!8997/%drh==iuh'
                . random_int(PHP_INT_MIN, PHP_INT_MAX)
                . random_int(PHP_INT_MIN, PHP_INT_MAX)
            )
            . '@unregistered.fyyn.io'
        );

        $user->addRole(Role::UNREGISTERED_USER);

        if ($asExtensionOnlyUser) {
            $user->addRole(Role::EXTENSION_ONLY_USER);
        }

        $user->setPassword(
            password_hash(
                random_int(PHP_INT_MIN, PHP_INT_MAX),
                PASSWORD_DEFAULT
            )
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(
            new UserCreatedSymfonyEvent($user)
        );

        return $user;
    }

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function handleUnregisteredUserClaimsEmail(
        User    $claimingUser,
        string  $claimedEmail,
        ?string $plainPassword
    ): bool
    {
        if (!$claimingUser->isUnregistered()) {
            throw new LogicException('Only unregistered user sessions can claim.');
        }

        /** @var User|null $existingUser */
        $existingUser = $this
            ->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $claimedEmail]);

        if (!is_null($existingUser)) {
            throw new Exception("A user with email '$claimedEmail' already exists.");
        }

        $claimingUser->setEmail($claimedEmail);
        $claimingUser->removeRole(Role::UNREGISTERED_USER);
        $claimingUser->addRole(Role::REGISTERED_USER);

        if (!is_null($plainPassword)) {
            $claimingUser->setPassword(
                $this->userPasswordHasher->hashPassword(
                    $claimingUser,
                    $plainPassword
                )
            );
        }

        $this->entityManager->persist($claimingUser);
        $this->entityManager->flush();

        $contactTags = [];
        if ($claimingUser->isExtensionOnly()) {
            $contactTags[] = ActiveCampaignContactTag::RegisteredThroughTheChromeExtension;
        }

        $this->messageBus->dispatch(
            new SyncUserToActiveCampaignCommandSymfonyMessage(
                $claimingUser,
                $contactTags
            )
        );

        $this
            ->presentationService
            ->sendVerificationEmailForClaimedUser($claimingUser);

        return true;
    }

    public function handleUnregisteredUserReclaimsEmail(
        User $userToClaim
    ): void
    {
        $this
            ->presentationService
            ->sendVerificationEmailForClaimedUser($userToClaim);
    }

    public function unregisteredUserClaimsRegisteredUser(
        User $claimingUser,
        User $claimedUser
    ): bool
    {
        if (!$claimingUser->isUnregistered()) {
            throw new LogicException('Only unregistered user sessions can claim.');
        }

        if (!$claimedUser->isRegistered()) {
            throw new LogicException('Only registered user can be claimed.');
        }

        /** @var RecordingSession $recordingSession */
        foreach ($claimingUser->getRecordingSessions() as $recordingSession) {
            $recordingSession->setUser($claimedUser);
            $recordingSession->setOrganization(
                $claimedUser->getCurrentlyActiveOrganization()
            );
            $this->entityManager->persist($recordingSession);
        }
        $claimingUser->setRecordingSessions([]);
        $this->entityManager->persist($claimingUser);

        /** @var Video $video */
        foreach ($claimingUser->getVideos() as $video) {
            $video->setUser($claimedUser);
            $video->setOrganization(
                $claimedUser->getCurrentlyActiveOrganization()
            );
            $this->entityManager->persist($video);
        }
        $claimingUser->setVideos([]);
        $this->entityManager->persist($claimingUser);

        $this->entityManager->persist($claimedUser);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(
            new UnregisteredUserClaimedRegisteredUserSymfonyEvent(
                $claimingUser,
                $claimedUser
            )
        );

        $this->entityManager->remove($claimingUser);
        $this->entityManager->flush();

        unset($claimingUser);

        return true;
    }

    public function userMustVerifyEmailBeforeUsingSite(
        User $user
    ): bool
    {
        return $user->isRegistered() && !$user->isVerified();
    }

    /**
     * @throws VerifyEmailExceptionInterface
     */
    public function handleVerificationRequest(
        Request $request,
        User    $user
    ): void
    {
        $this->verifyEmailHelper->validateEmailConfirmation(
            $request->getUri(),
            $user->getId(),
            $user->getEmail()
        );

        $this->makeUserVerified($user);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function makeUserVerified(
        User $user
    ): bool
    {
        if (!$user->isRegistered()) {
            throw new LogicException('Only registered user can be verified.');
        }

        if ($user->isVerified()) {
            throw new LogicException('User is already verified.');
        }

        $user->setIsVerified(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(
            new UserVerifiedSymfonyEvent($user)
        );

        return true;
    }

    public function updatePassword(
        User   $user,
        string $plainPassword
    ): void
    {
        $user->setPassword(
            $this->userPasswordHasher->hashPassword(
                $user,
                $plainPassword
            )
        );
    }

    public function switchVideosListViewMode(
        User $user
    ): void
    {
        if ($user->getVideosListViewMode() === VideosListViewMode::Tiles) {
            $user->setVideosListViewMode(VideosListViewMode::Dense);
        } else {
            $user->setVideosListViewMode(VideosListViewMode::Tiles);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function userCanSignIn(
        ?User $user
    ): bool
    {
        if (is_null($user)) {
            return true;
        }

        return $user->isUnregistered();
    }

    public function userCanSignUp(
        ?User $user
    ): bool
    {
        return $this->userCanSignIn($user);
    }

    public function userCanSignOut(
        ?User $user
    ): bool
    {
        if (is_null($user)) {
            return false;
        }

        return $user->isRegistered();
    }

    public function userIsSignedIn(
        ?User $user
    ): bool
    {
        return $this->userCanSignOut($user);
    }
}
