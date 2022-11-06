<?php

namespace App\VideoBasedMarketing\Account\Domain\Enum;

enum VotingAttribute: string
{
    case View = 'view';
    case Edit = 'edit';
    case Use = 'use';
    case Delete = 'delete';
}
