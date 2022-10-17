<?php

namespace App\Service\Aspect\Cookies;

use App\Service\Aspect\DateAndTime\DateAndTimeService;
use DateInterval;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;


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

    /**
     * @throws Exception
     */
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

    public function getClientTimezone(Request $request): string
    {
        $clientTimezone = $request->cookies->get(CookieName::ClientTimezone->value);

        if (is_null($clientTimezone)) {
            return 'Europe/Berlin';
        } else {
            return $clientTimezone;
        }
    }
}
