<?php

namespace App\Shared\Infrastructure\Enum;

enum CookieName: string
{
    case CookieConsent = 'CookieConsent';
    case PHPSESSID = 'PHPSESSID';
    case REMEMBERME = 'REMEMBERME';
    case ClientId = 'mercuriusClientId';
    case ClientTimezone = 'mercuriusClientTimezone';
}
