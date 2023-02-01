<?php

namespace App\VideoBasedMarketing\Settings\Domain\Enum;

enum SetCustomDomainNameResult: int
{
    case Success = 0;

    case InvalidDomainName = 1;

    case IsApexDomain = 2;

    case IsMercuriusDomain = 3;
}
