<?php

namespace App\VideoBasedMarketing\Account\Infrastructure\Enum;

enum ActiveCampaignContactTag: int
{
    case RegisteredThroughTheChromeExtension = 9;
    case EmailIsValidated = 10;
    case EnvDev = 11;
    case EnvPreprod = 12;
    case EnvProd = 13;
    case EnvTest = 14;
}
