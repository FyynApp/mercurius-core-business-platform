<?php

namespace App\Shared\Infrastructure\Service;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Exception;


class DateAndTimeService
{
    /**
     * @throws Exception
     */
    public static function getDateTime(
        string  $s = 'now',
        ?string $tz = 'UTC'
    ): DateTime
    {
        if (is_null($tz)) {
            $tz = 'UTC';
        }

        return new DateTime(
            $s,
            new DateTimeZone($tz)
        );
    }

    /**
     * @throws Exception
     */
    public static function getDateTimeImmutable(
        string  $s = 'now',
        ?string $tz = 'UTC'
    ): DateTimeImmutable
    {
        if (is_null($tz)) {
            $tz = 'UTC';
        }

        return new DateTimeImmutable(
            $s,
            new DateTimeZone($tz)
        );
    }
}
