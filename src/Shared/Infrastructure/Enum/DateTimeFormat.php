<?php

namespace App\Shared\Infrastructure\Enum;

enum DateTimeFormat: string
{
    case SecondsSinceUnixEpoch = 'U';
    case Iso8601 = 'c';
}
