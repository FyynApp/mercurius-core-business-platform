<?php

namespace App\Shared\Domain\Enum;

enum Bcp47LanguageCode: string
{
    case EnUs = 'en-US';
    case DeDe = 'de-DE';
    case NlNl = 'nl-NL';
    case EsEs = 'es-ES';
    case FrFr = 'fr-FR';
    case ItIt = 'it-IT';
    case TrTr = 'tr-TR';
    case PtPt = 'pt-PT';
    case PtBr = 'pt-BR';
    case PlPl = 'pl-PL';

    case HiIn = 'hi-IN';
    case BnBd = 'bn-BD';
    case BnIn = 'bn-IN';

    case RuRu = 'ru-RU';
    case CmnHansCn = 'cmn-Hans-CN';

    case HeIl = 'he-IL';
}
