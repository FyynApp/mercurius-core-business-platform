<?php

namespace App\VideoBasedMarketing\Membership\Domain\Enum;

enum PackageName: string
{
    case LingoSyncCreditsFor5Minutes = 'lingoSyncCreditsFor5Minutes';
    case LingoSyncCreditsFor10Minutes = 'lingoSyncCreditsFor10Minutes';
    case LingoSyncCreditsFor30Minutes = 'lingoSyncCreditsFor30Minutes';
    case LingoSyncCreditsFor60Minutes = 'lingoSyncCreditsFor60Minutes';
}
