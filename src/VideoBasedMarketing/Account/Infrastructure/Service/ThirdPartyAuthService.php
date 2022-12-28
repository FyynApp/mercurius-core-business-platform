<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Enum\Role;
use App\VideoBasedMarketing\Account\Infrastructure\Entity\HandleReceivedLinkedInResourceOwnerResult;
use App\VideoBasedMarketing\Account\Infrastructure\Entity\ThirdPartyAuthLinkedinResourceOwner;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use League\OAuth2\Client\Provider\LinkedInResourceOwner;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class ThirdPartyAuthService
{
    private EntityManagerInterface $entityManager;

    private UserPasswordHasherInterface $userPasswordHasher;

    private RequestParametersBasedUserAuthService $requestParametersBasedUserAuthService;

    public function __construct(
        EntityManagerInterface                $entityManager,
        UserPasswordHasherInterface           $userPasswordHasher,
        RequestParametersBasedUserAuthService $requestParametersBasedUserAuthService,
    )
    {
        $this->entityManager = $entityManager;
        $this->userPasswordHasher = $userPasswordHasher;
        $this->requestParametersBasedUserAuthService = $requestParametersBasedUserAuthService;
    }

    public function userMustBeRedirectedToThirdPartyAuthLinkedinEndpoint(
        string $email
    ): bool
    {
        /** @var ?User $user */
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(
                ['email' => $email]
            );

        if (is_null($user)) {
            return false;
        }

        if (is_null($user->getThirdPartyAuthLinkedinResourceOwner())) {
            return false;
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public function handleReceivedLinkedInResourceOwner(
        LinkedInResourceOwner $receivedResourceOwner
    ): HandleReceivedLinkedInResourceOwnerResult
    {
        if (   is_null($receivedResourceOwner->getId())
            || is_null($receivedResourceOwner->getEmail())
        ) {
            return new HandleReceivedLinkedInResourceOwnerResult(
                false,
                HandleReceivedLinkedInResourceOwnerResult::ERROR_MISSING_ID_OR_EMAIL
            );
        }

        $resourceOwner = $this->entityManager->find(
            ThirdPartyAuthLinkedinResourceOwner::class,
            $receivedResourceOwner->getId()
        );

        if (is_null($resourceOwner)) {
            $resourceOwner = new ThirdPartyAuthLinkedinResourceOwner(
                $receivedResourceOwner->getId(),
                $receivedResourceOwner->getEmail(),
                $receivedResourceOwner->getFirstName(),
                $receivedResourceOwner->getLastName()
            );
        } else {
            if ($receivedResourceOwner->getEmail() !== $resourceOwner->getEmail()) {
                return new HandleReceivedLinkedInResourceOwnerResult(
                    false,
                    HandleReceivedLinkedInResourceOwnerResult::ERROR_RETRIEVED_EMAIL_DIFFERS_FROM_STORED_EMAIL
                );
            }

            $resourceOwner->setFirstName($receivedResourceOwner->getFirstName());
            $resourceOwner->setLastName($receivedResourceOwner->getLastName());
        }

        if (is_null($resourceOwner->getUser())) {
            $user = $this->entityManager->getRepository(User::class)
                                        ->findOneBy(['email' => $resourceOwner->getEmail()]);
            if (is_null($user)) {
                $user = new User();
                $user->setEmail($resourceOwner->getEmail());
                $user->setPassword(
                    $this->userPasswordHasher->hashPassword(
                        $user,
                        random_bytes(255)
                    )
                );
            }
            $user->setIsVerified(true);
            $user->addRole(Role::REGISTERED_USER);
            $resourceOwner->setUser($user);

            $this->entityManager->persist($resourceOwner);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        if (array_key_exists(3, $receivedResourceOwner->getSortedProfilePictures())) {
            if (array_key_exists('url', $receivedResourceOwner->getSortedProfilePictures()[3])) {
                $resourceOwner->setSortedProfilePicture800Url(
                    $receivedResourceOwner->getSortedProfilePictures()[3]['url']
                );
            }
            if (array_key_exists('contentType', $receivedResourceOwner->getSortedProfilePictures()[3])) {
                $resourceOwner->setSortedProfilePicture800ContentType(
                    $receivedResourceOwner->getSortedProfilePictures()[3]['contentType']
                );
            }

        }

        $this->entityManager->persist($resourceOwner);
        $this->entityManager->flush();

        $loginLinkUrl = null;
        if (!is_null($resourceOwner->getUser())) {
            $loginLinkUrl = $this
                ->requestParametersBasedUserAuthService
                ->createUrl(
                  $resourceOwner->getUser(),
                  'shared.presentation.contentpages.homepage'
                );
        }

        return new HandleReceivedLinkedInResourceOwnerResult(
            true,
            null,
            $resourceOwner,
            $loginLinkUrl
        );
    }
}
