<?php

namespace App\VideoBasedMarketing\Membership\Domain\Enum;

enum MembershipPlanName: string
{
    case Basic = 'basic';
    case Plus = 'plus';
    case Pro = 'pro';
}
