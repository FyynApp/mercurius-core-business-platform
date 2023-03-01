<?php

namespace App\VideoBasedMarketing\Account\Api\Extension\V1\Service;

use App\VideoBasedMarketing\Account\Api\Extension\V1\Entity\SessionInfo;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Service\AccountDomainService;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipService;
use Exception;


class SessionService
{
    private MembershipService $membershipService;

    private AccountDomainService $accountDomainService;

    public function __construct(
        MembershipService    $membershipService,
        AccountDomainService $accountDomainService
    )
    {
        $this->membershipService = $membershipService;
        $this->accountDomainService = $accountDomainService;
    }

    /**
     * @throws Exception
     */
    public function getSessionInfo(
        ?User $user
    ): SessionInfo
    {
        if (is_null($user)) {
            $user = $this
                ->accountDomainService
                ->createUnregisteredUser(true);
        }

        return new SessionInfo(
            $user,
            $this
                ->membershipService
                ->getSubscribedMembershipPlanForCurrentlyActiveOrganization($user)
        );
    }
}
