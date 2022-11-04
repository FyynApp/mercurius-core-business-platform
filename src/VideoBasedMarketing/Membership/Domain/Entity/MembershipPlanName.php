<?php

namespace App\VideoBasedMarketing\Membership\Domain\Entity;

enum MembershipPlanName: string
{
    case Basic = 'basic';
    case Plus = 'plus';
    case Pro = 'pro';
}
