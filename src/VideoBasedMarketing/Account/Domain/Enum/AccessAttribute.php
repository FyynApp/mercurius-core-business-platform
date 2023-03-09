<?php

namespace App\VideoBasedMarketing\Account\Domain\Enum;

enum AccessAttribute: string
{
    case View = 'view';
    case Edit = 'edit';
    case Use = 'use';
    case Delete = 'delete';
}
