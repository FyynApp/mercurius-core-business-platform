<?php

namespace App\VideoBasedMarketing\Account\Api\Extension\V1\Service;

use App\Shared\ContentDelivery\Infrastructure\Service\ContentDeliveryService;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipService;
use InvalidArgumentException;


class SessionService
{
    private ContentDeliveryService $contentDeliveryService;

    private MembershipService $membershipService;

    public function __construct(
        ContentDeliveryService $contentDeliveryService,
        MembershipService      $membershipService
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
