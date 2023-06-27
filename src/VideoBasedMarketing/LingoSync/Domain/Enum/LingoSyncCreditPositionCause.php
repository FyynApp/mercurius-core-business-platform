<?php

namespace App\VideoBasedMarketing\LingoSync\Domain\Enum;


enum LingoSyncCreditPositionCause: string
{
    case LingoSyncProcess = 'lingoSyncProcess';
    case Purchase = 'purchase';
    case Subscription = 'subscription';
    case UserVerification = 'userVerification';
}
