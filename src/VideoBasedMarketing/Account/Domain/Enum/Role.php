<?php

namespace App\VideoBasedMarketing\Account\Domain\Enum;

enum Role: string
{
    case USER = 'ROLE_USER';

    case REGISTERED_USER = 'ROLE_REGISTERED_USER';
    case UNREGISTERED_USER = 'ROLE_UNREGISTERED_USER';

    case EXTENSION_ONLY_USER = 'ROLE_EXTENSION_ONLY_USER';

    case ADMIN = 'ROLE_ADMIN';
}
