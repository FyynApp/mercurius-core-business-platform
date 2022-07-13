<?php

namespace App\Service\Feature\Account;

use App\Entity\Feature\Account\HandleReceivedLinkedInResourceOwnerResult;
use App\Entity\Feature\Account\ThirdPartyAuthLinkedinResourceOwner;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\LinkedInResourceOwner;
use Throwable;

class ThirdPartyAuthService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function handleReceivedLinkedInResourceOwner(LinkedInResourceOwner $receivedResourceOwner): HandleReceivedLinkedInResourceOwnerResult
    {
        try {
            if (   is_null($receivedResourceOwner->getId())
                || is_null($receivedResourceOwner->getEmail())
            ) {
                return new HandleReceivedLinkedInResourceOwnerResult(
                    false,
                    HandleReceivedLinkedInResourceOwnerResult::ERROR_MISSING_ID_OR_EMAIL
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
                    return new HandleReceivedLinkedInResourceOwnerResult(
                        false,
                        HandleReceivedLinkedInResourceOwnerResult::ERROR_RETRIEVED_EMAIL_DIFFERS_FROM_STORED_EMAIL
                    );
                }

                $resourceOwner->setFirstName($receivedResourceOwner->getFirstName());
                $resourceOwner->setLastName($receivedResourceOwner->getLastName());
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

            return new HandleReceivedLinkedInResourceOwnerResult(true, null, $resourceOwner);

        } catch (Throwable $t) {
            return new HandleReceivedLinkedInResourceOwnerResult(
                false,
                HandleReceivedLinkedInResourceOwnerResult::ERROR_THROWABLE
            );
        }
    }
}
