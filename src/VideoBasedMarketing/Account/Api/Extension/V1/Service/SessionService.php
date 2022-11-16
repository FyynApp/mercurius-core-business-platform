<?php

namespace App\VideoBasedMarketing\Account\Api\Extension\V1\Service;

use App\VideoBasedMarketing\Account\Api\Extension\V1\Entity\SessionInfo;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Service\UserDomainService;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipService;
use Exception;


class SessionService
{
    private MembershipService $membershipService;

    private UserDomainService $userService;

    public function __construct(
        MembershipService    $membershipService,
        UserDomainService $userService
    )
    {
        $this->membershipService = $membershipService;
        $this->userService = $userService;
    }

    /**
     * @throws Exception
     */
    public function getSessionInfo(
        ?User $user
    ): SessionInfo
    {
        if (is_null($user)) {
            $user = $this->userService->createUnregisteredUser();
        }

        return new SessionInfo(
            $user,
            $this
                ->membershipService
                ->getCurrentlySubscribedMembershipPlanForUser($user)
        );
    }
}
