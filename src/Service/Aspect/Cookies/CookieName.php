<?php

namespace App\Service\Aspect\Cookies;

enum CookieName: string
{
    case ClientId = 'mercuriusBoundedContextClientId';
    case ClientTimezone = 'mercuriusBoundedContextClientTimezone';
}
