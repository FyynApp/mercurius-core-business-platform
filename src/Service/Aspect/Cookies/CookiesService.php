<?php

namespace App\Service\Aspect\Cookies;

use App\Service\Aspect\DateAndTime\DateAndTimeService;
use DateInterval;
use DateTime;
use Symfony\Component\HttpFoundation\Cookie;


class CookiesService
{
    public static function createCookieObject(
        CookieName   $name,
        string       $value = null,
        DateTime|int $expire = 0,
        string       $path = '/',
        ?string      $domain = null,
        bool         $secure = true,
        bool         $httpOnly = true,
        bool         $raw = false,
        string       $sameSite = Cookie::SAMESITE_STRICT
    ): Cookie
    {
        return new Cookie(
            $name->value,
            $value,
            $expire,
            $path,
            $domain,
            $secure,
            $httpOnly,
            $raw,
            $sameSite
        );
    }

    public static function getCookieExpireValue(CookieName $cookieName): DateTime|int
    {
        $expireAt = DateAndTimeService::getDateTimeUtc();

        if ($cookieName === CookieName::ClientId) {
            $expireAt->add(new DateInterval('P10Y'));
        } else {
            return 0; // Cookie is valid until end of session
        }

        return $expireAt;
    }
}
