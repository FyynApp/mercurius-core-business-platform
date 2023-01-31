<?php

namespace App\VideoBasedMarketing\Settings\Domain\Enum;

enum CustomDomainDnsSetupStatus: int
{
    case CheckOutstanding = 0;
    case CheckRunning = 1;
    case CheckPositive = 2;
    case CheckNegative = 3;
    case CheckErrored = 4;
}
