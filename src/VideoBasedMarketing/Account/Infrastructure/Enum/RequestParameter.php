<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\Enum;

enum RequestParameter: string
{
    case UnregisteredUserId = 'unregisteredUserId';
    case UnregisteredUserAuthHash = 'unregisteredUserAuthHash';
}
