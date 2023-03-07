<?php

namespace App\VideoBasedMarketing\Membership\Domain\Enum;

enum MembershipPlanName: string
{
    case Basic = 'basic';
    case Independent = 'independent';
    case Plus = 'plus';
    case Pro = 'pro';
}
