<?php

namespace App\Service\Aspect\DateAndTime;

use DateTime;

class DateAndTimeService
{
    public static function getDateTimeUtc(string $s = 'now'): DateTime
    {
        return new DateTime($s);
    }
}
