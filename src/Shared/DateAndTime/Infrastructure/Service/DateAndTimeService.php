<?php

namespace App\Shared\DateAndTime\Infrastructure\Service;

use DateTime;
use Exception;


class DateAndTimeService
{
    /**
     * @throws Exception
     */
    public static function getDateTimeUtc(string $s = 'now'): DateTime
    {
        return new DateTime($s);
    }
}
