<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\Enum;

enum RequestParameter: string
{
    case RequestParametersBasedUserAuthId = '__requestParametersBasedUserAuthId';
    case RequestParametersBasedUserAuthValidUntil = '__requestParametersBasedUserAuthValidUntil';
    case RequestParametersBasedUserAuthHash = '__requestParametersBasedUserAuthHash';
}
