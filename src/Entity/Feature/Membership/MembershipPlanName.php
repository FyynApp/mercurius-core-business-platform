<?php

namespace App\Entity\Feature\Membership;

enum MembershipPlanName: string
{
    case Basic = 'basic';
    case Plus = 'plus';
    case Pro = 'pro';
}
