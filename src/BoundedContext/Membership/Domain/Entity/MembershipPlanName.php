<?php

namespace App\BoundedContext\Membership\Domain\Entity;

enum MembershipPlanName: string
{
    case Basic = 'basic';
    case Plus = 'plus';
    case Pro = 'pro';
}
