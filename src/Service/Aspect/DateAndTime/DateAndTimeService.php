<?php

namespace App\Service\Aspect\DateAndTime;

use DateTime;
use DateTimeInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;


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
