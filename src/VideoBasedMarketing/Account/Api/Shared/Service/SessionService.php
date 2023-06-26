<?php

namespace App\VideoBasedMarketing\Account\Api\Shared\Service;

use App\VideoBasedMarketing\Account\Api\Shared\Entity\SessionInfo;
use App\VideoBasedMarketing\Account\Domain\Entity\User;
use App\VideoBasedMarketing\Account\Domain\Service\AccountDomainService;
use App\VideoBasedMarketing\Membership\Domain\Service\MembershipPlanService;
use Exception;


class SessionService
{
    private MembershipPlanService $membershipPlanService;

    private AccountDomainService $accountDomainService;

    public function __construct(
        MembershipPlanService    $membershipPlanService,
        AccountDomainService $accountDomainService
    )
    {
        $this->membershipPlanService = $membershipPlanService;
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
                ->membershipPlanService
                ->getSubscribedMembershipPlanForCurrentlyActiveOrganization($user)
        );
    }
}
