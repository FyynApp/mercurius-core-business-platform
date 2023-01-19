<?php

namespace App\Shared\Domain\Service;

use App\Shared\Domain\Enum\Iso639_1Code;
use Symfony\Component\HttpFoundation\Request;

readonly class Iso639_1CodeService
{
    public static function getCodeFromRequest(
        Request $request
    ): Iso639_1Code
    {
        $preferredLanguage = $request->getPreferredLanguage();

        if (   $preferredLanguage === Iso639_1Code::De->value
            || mb_substr($preferredLanguage, 0, 3) === Iso639_1Code::De->value . '_'
            || mb_substr($preferredLanguage, 0, 3) === Iso639_1Code::De->value . '-'
        ) {
            return Iso639_1Code::De;
        }

        return Iso639_1Code::En;
    }
}
