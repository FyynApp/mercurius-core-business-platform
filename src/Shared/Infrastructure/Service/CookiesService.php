<?php

namespace App\Shared\Infrastructure\Service;

use App\Shared\Infrastructure\Enum\CookieName;
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
        string       $sameSite = Cookie::SAMESITE_NONE
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
    public static function getCookieExpireValue(
        CookieName $cookieName
    ): DateTime|int
    {
        $expireAt = DateAndTimeService::getDateTime();

        if ($cookieName === CookieName::ClientId) {
            $expireAt->add(new DateInterval('P10Y'));
        } else {
            return 0; // Cookie is valid until end of session
        }

        return $expireAt;
    }

    public function getClientTimezone(
        Request $request
    ): string
    {
        $clientTimezone = $request->cookies->get(CookieName::ClientTimezone->value);

        if (is_null($clientTimezone)) {
            return 'Europe/Berlin';
        } else {
            return $clientTimezone;
        }
    }

    public function isCookieAllowed(
        Request    $request,
        CookieName $cookieName
    ): bool
    {
        if (   $cookieName === CookieName::CookieConsent
            || $cookieName === CookieName::PHPSESSID
            || $cookieName === CookieName::REMEMBERME
            || $cookieName === CookieName::ClientTimezone
        ) {
            return true;
        }

        $cookieConsent = $request->cookies->get(CookieName::CookieConsent->value);

        if (   is_null($cookieConsent)
            || $cookieConsent == '-1'
        ) {
            return true;
        }

        $validPhpJson = preg_replace(
            '/\s*:\s*([a-zA-Z0-9_]+?)([}\[,])/',
            ':"$1"$2',
            preg_replace(
                '/([{\[,])\s*([a-zA-Z0-9_]+?):/',
                '$1"$2":',
                str_replace("'", '"',stripslashes($cookieConsent))
            )
        );
        $consentDetails = json_decode($validPhpJson);

        if (is_null($consentDetails)) {
            return true;
        }

        if ($cookieName === CookieName::ClientId) {
            return filter_var($consentDetails->statistics, FILTER_VALIDATE_BOOLEAN);
        }

        return false;
    }
}
