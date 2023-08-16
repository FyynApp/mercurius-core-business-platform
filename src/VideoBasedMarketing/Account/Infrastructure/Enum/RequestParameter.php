<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\Enum;

enum RequestParameter: string
{
    case RequestParametersBasedUserAuthId = '__rpbuai';
    case RequestParametersBasedUserAuthValidUntil = '__rpbuavu';
    case RequestParametersBasedUserAuthHash = '__rpbuah';

    case IsAuthForApp = '__iafa';
}
