<?php

namespace App\VideoBasedMarketing\Membership\Domain\Enum;

enum Capability: string
{
    case CustomLogoOnLandingpage = 'CustomLogoOnLandingpage';
    case CustomDomain = 'CustomDomain';
    case AdFreeLandingpages = 'AdFreeLandingpages';
    case BrandingFreeEmbeddableVideoPlayer = 'BrandingFreeEmbeddableVideoPlayer';
}
