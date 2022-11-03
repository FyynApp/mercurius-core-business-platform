<?php

namespace App\Service\Feature\Account;

use App\BoundedContext\Account\Application\Entity\HandleReceivedLinkedInResourceOwnerResult;
use App\BoundedContext\Account\Application\Entity\ThirdPartyAuthLinkedinResourceOwner;
use App\BoundedContext\Account\Domain\Entity\Role;
use App\BoundedContext\Account\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use League\OAuth2\Client\Provider\LinkedInResourceOwner;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;


class ThirdPartyAuthService
{
    private EntityManagerInterface $entityManager;

    private UserPasswordHasherInterface $userPasswordHasher;

    private LoginLinkHandlerInterface $loginLinkHandler;

    public function __construct(
        EntityManagerInterface      $entityManager,
        UserPasswordHasherInterface $userPasswordHasher,
        LoginLinkHandlerInterface   $loginLinkHandler
    )
    {
        $this->entityManager = $entityManager;
        $this->userPasswordHasher = $userPasswordHasher;
        $this->loginLinkHandler = $loginLinkHandler;
    }

    /**
     * @throws Exception
     */
    public function handleReceivedLinkedInResourceOwner(
        LinkedInResourceOwner $receivedResourceOwner
    ): \App\BoundedContext\Account\Application\Entity\HandleReceivedLinkedInResourceOwnerResult
    {
        if (is_null($receivedResourceOwner->getId())
            || is_null($receivedResourceOwner->getEmail())
        ) {
            return new HandleReceivedLinkedInResourceOwnerResult(
                false,
                \App\BoundedContext\Account\Application\Entity\HandleReceivedLinkedInResourceOwnerResult::ERROR_MISSING_ID_OR_EMAIL
            );
        }

        $resourceOwner = $this->entityManager->find(ThirdPartyAuthLinkedinResourceOwner::class, $receivedResourceOwner->getId());

        if (is_null($resourceOwner)) {
            $resourceOwner = new ThirdPartyAuthLinkedinResourceOwner(
                $receivedResourceOwner->getId(),
                $receivedResourceOwner->getEmail(),
                $receivedResourceOwner->getFirstName(),
                $receivedResourceOwner->getLastName()
            );
        } else {
            if ($receivedResourceOwner->getEmail() !== $resourceOwner->getEmail()) {
                return new \App\BoundedContext\Account\Application\Entity\HandleReceivedLinkedInResourceOwnerResult(
                    false,
                    \App\BoundedContext\Account\Application\Entity\HandleReceivedLinkedInResourceOwnerResult::ERROR_RETRIEVED_EMAIL_DIFFERS_FROM_STORED_EMAIL
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
            $loginLinkDetails = $this->loginLinkHandler->createLoginLink($resourceOwner->getUser());
            $loginLinkUrl = $loginLinkDetails->getUrl();
        }

        return new HandleReceivedLinkedInResourceOwnerResult(
            true,
            null,
            $resourceOwner,
            $loginLinkUrl
        );
    }
}
