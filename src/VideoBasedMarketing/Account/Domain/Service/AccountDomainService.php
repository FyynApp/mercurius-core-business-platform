<?php

namespace App\VideoBasedMarketing\Account\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Enum\Role;
use App\VideoBasedMarketing\Account\Domain\Event\UnregisteredUserClaimedRegisteredUserEvent;
use App\VideoBasedMarketing\Account\Domain\Event\UserCreatedEvent;
use App\VideoBasedMarketing\Account\Infrastructure\Enum\ActiveCampaignContactTag;
use App\VideoBasedMarketing\Account\Infrastructure\Event\UserVerifiedEvent;
use App\VideoBasedMarketing\Account\Infrastructure\Message\SyncUserToActiveCampaignCommandMessage;
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
            new UserCreatedEvent($user)
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
            new UserCreatedEvent($user)
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
            new SyncUserToActiveCampaignCommandMessage(
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
        User $unregisteredUser,
        User $registeredUser
    ): bool
    {
        if (!$unregisteredUser->isUnregistered()) {
            throw new LogicException('Only unregistered user sessions can claim.');
        }

        if (!$registeredUser->isRegistered()) {
            throw new LogicException('Only registered user can be claimed.');
        }

        /** @var RecordingSession $recordingSession */
        foreach ($unregisteredUser->getRecordingSessions() as $recordingSession) {
            $recordingSession->setUser($registeredUser);
            $this->entityManager->persist($recordingSession);
        }
        $unregisteredUser->setRecordingSessions([]);
        $this->entityManager->persist($unregisteredUser);

        /** @var Video $video */
        foreach ($unregisteredUser->getVideos() as $video) {
            $video->setUser($registeredUser);
            $this->entityManager->persist($video);
        }
        $unregisteredUser->setVideos([]);
        $this->entityManager->persist($unregisteredUser);

        $this->entityManager->persist($registeredUser);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(
            new UnregisteredUserClaimedRegisteredUserEvent(
                $unregisteredUser,
                $registeredUser
            )
        );

        $this->entityManager->remove($unregisteredUser);
        $this->entityManager->flush();

        unset($unregisteredUser);

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
            new UserVerifiedEvent($user)
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
}
