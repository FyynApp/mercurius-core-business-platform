<?php

namespace App\Service\Aspect\Cookies;

enum CookieName: string
{
    case ClientId = 'mercuriusClientId';
    case ClientTimezone = 'mercuriusClientTimezone';
}
