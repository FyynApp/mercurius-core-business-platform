<?php

namespace App\VideoBasedMarketing\Account\Api\Extension\V1\Service;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipService;
use InvalidArgumentException;


class SessionService
{
    private \App\Shared\Infrastructure\Service\ContentDeliveryService $contentDeliveryService;

    private MembershipService $membershipService;

    public function __construct(
        \App\Shared\Infrastructure\Service\ContentDeliveryService $contentDeliveryService,
        MembershipService                                         $membershipService
    )
    {
        $this->contentDeliveryService = $contentDeliveryService;
        $this->membershipService = $membershipService;
    }

    public function getSessionInfoArray(?User $user): array
    {
        if (is_null($user)) {
            throw new InvalidArgumentException('User required.');
        }

        return [
            'userIsLoggedIn' => true,

            'userIsRegistered' => $user->isRegistered(),

            'userName' => $user->getUserIdentifier(),

            'userFirstName' => $user->getFirstName(),

            'userLastName' => $user->getLastName(),

            'userImage' => $this
                ->contentDeliveryService
                ->getUrlForUserProfilePhoto($user),

            'membershipPlan' => $this->membershipService
                ->getCurrentlySubscribedMembershipPlanForUser($user)
                ->getName()
                ->value
        ];
    }
}
