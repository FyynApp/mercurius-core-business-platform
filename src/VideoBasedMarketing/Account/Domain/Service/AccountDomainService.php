<?php

namespace App\VideoBasedMarketing\Account\Domain\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Enum\Role;
use App\VideoBasedMarketing\Account\Infrastructure\Enum\ActiveCampaignContactTag;
use App\VideoBasedMarketing\Account\Infrastructure\Message\SyncUserToActiveCampaignCommandMessage;
use App\VideoBasedMarketing\Account\Presentation\Service\AccountPresentationService;
use App\VideoBasedMarketing\Recordings\Domain\Entity\RecordingSession;
use App\VideoBasedMarketing\Recordings\Domain\Entity\Video;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use LogicException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;


class AccountDomainService
{
    private EntityManagerInterface $entityManager;

    private AccountPresentationService $presentationService;

    private MessageBusInterface $messageBus;


    public function __construct(
        EntityManagerInterface     $entityManager,
        AccountPresentationService $presentationService,
        MessageBusInterface        $messageBus
    )
    {
        $this->entityManager = $entityManager;
        $this->presentationService = $presentationService;
        $this->messageBus = $messageBus;
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
                . random_int(PHP_INT_MIN,  PHP_INT_MAX)
                . random_int(PHP_INT_MIN,  PHP_INT_MAX)
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

        return $user;
    }

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function handleUnregisteredUserClaimsEmail(
        User   $userToClaim,
        string $claimEmail
    ): bool
    {
        if (!$userToClaim->isUnregistered()) {
            throw new LogicException('Only unregistered user sessions can claim.');
        }

        /** @var User|null $existingUser */
        $existingUser = $this
            ->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $claimEmail]);

        if (!is_null($existingUser)) {
            throw new Exception("A user with email '$claimEmail' already exists.");
        }

        $userToClaim->setEmail($claimEmail);
        $userToClaim->makeRegistered();

        $this->entityManager->persist($userToClaim);
        $this->entityManager->flush();

        $contactTags = [];
        if ($userToClaim->isExtensionOnly()) {
            $contactTags[] = ActiveCampaignContactTag::RegisteredThroughTheChromeExtension;
        }

        $this->messageBus->dispatch(
            new SyncUserToActiveCampaignCommandMessage(
                $userToClaim,
                $contactTags
            )
        );

        $this
            ->presentationService
            ->sendVerificationEmailForClaimedUser($userToClaim);

        return true;
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

        return true;
    }
}
