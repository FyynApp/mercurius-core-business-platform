<?php

namespace App\VideoBasedMarketing\Membership\Domain\Enum;

enum MembershipPlanName: string
{
    case Basic = 'basic';
    case Independent = 'independent';
    case Professional = 'professional';
    case Ultimate = 'ultimate';
}
